<?php

namespace App\Services;

use App\Models\Logro;
use App\Models\Partido;
use App\Models\Torneo;
use App\Support\Logros\Catalogo;
use App\Support\Torneos\Tabla;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Otorga los logros recalculándolos desde los datos (partidos, goles, tarjetas, lista,
 * pichangas). Es idempotente: agrega los que faltan y quita los que dejaron de cumplirse
 * (p. ej. si el admin borra un gol mal cargado). Los Pro solo se otorgan si el campeonato
 * es Pro en ese momento, pero no se pierden al vencer el plan.
 * Usa consultas sin scope de liga: corre desde observers, comandos o webhooks.
 */
class Logros
{
    private const UMBRALES_GOLES = [1 => 'primer_gol', 10 => 'goleador_10', 25 => 'goleador_25', 50 => 'goleador_50'];

    private const UMBRALES_PARTIDOS = [10 => 'partidos_10', 50 => 'partidos_50', 100 => 'partidos_100'];

    private const POSICION_ARQUERO = '/arquer|porter|goler|arco/i';

    /** @var array<int, bool> */
    private array $ligasPro = [];

    // Corre el cálculo sin romper el flujo que lo disparó (finalizar un partido, etc.).
    public static function seguro(Closure $tarea): void
    {
        try {
            $tarea(app(self::class));
        } catch (Throwable $e) {
            if (app()->runningUnitTests()) {
                throw $e;
            }
            report($e);
        }
    }

    // Tras finalizar, corregir o borrar un partido: los jugadores de ambos equipos y el torneo.
    public function despuesDePartido(Partido $partido): void
    {
        $ids = DB::table('jugadores')
            ->whereIn('equipo_id', [$partido->equipo_local_id, $partido->equipo_visita_id])
            ->pluck('id');

        foreach ($ids as $id) {
            $this->recalcularJugador($id);
        }

        if ($partido->torneo_id) {
            $this->recalcularTorneo($partido->torneo_id);
        }
    }

    // Todo un campeonato (al pasar a Pro o con el comando de recálculo).
    public function recalcularLiga(int $ligaId): void
    {
        $equipos = DB::table('equipos')->where('liga_id', $ligaId)->pluck('id');

        foreach (DB::table('jugadores')->whereIn('equipo_id', $equipos)->pluck('id') as $id) {
            $this->recalcularJugador($id);
        }

        foreach (DB::table('torneos')->where('liga_id', $ligaId)->pluck('id') as $id) {
            $this->recalcularTorneo($id);
        }
    }

    // ---------------------------------------------------------------- jugador

    public function recalcularJugador(int $jugadorId): void
    {
        $jugador = $this->ficha($jugadorId);

        if (! $jugador) {
            return;
        }

        $partidos = $this->partidosJugados($jugador);
        $deseados = [];
        $otorgar = function (string $clave, object $partido) use (&$deseados, $jugador) {
            $deseados["{$clave}|j{$jugador->id}"] ??= [
                'clave' => $clave, 'jugador_id' => $jugador->id, 'liga_id' => $jugador->liga_id,
                'partido_id' => $partido->id, 'obtenido_en' => $partido->cuando,
            ];
        };

        $goles = 0;
        $sinTarjeta = 0;
        $conGol = 0;

        foreach ($partidos->values() as $i => $p) {
            if ($i === 0) {
                $otorgar('debut', $p);
            }
            if (isset(self::UMBRALES_PARTIDOS[$i + 1])) {
                $otorgar(self::UMBRALES_PARTIDOS[$i + 1], $p);
            }

            $antes = $goles;
            $goles += $p->goles_jugador;
            foreach (self::UMBRALES_GOLES as $n => $clave) {
                if ($antes < $n && $goles >= $n) {
                    $otorgar($clave, $p);
                }
            }
            if ($p->goles_jugador >= 3) {
                $otorgar('hat_trick', $p);
            }

            if ($p->arquero && $p->goles_contra === 0) {
                $otorgar('valla_invicta', $p);
            }

            $sinTarjeta = $p->tarjetas_jugador > 0 ? 0 : $sinTarjeta + 1;
            if ($sinTarjeta >= 10) {
                $otorgar('fair_play', $p);
            }

            $conGol = $p->goles_jugador > 0 ? $conGol + 1 : 0;
            if ($conGol >= 5) {
                $otorgar('racha_goleadora', $p);
            }
        }

        $this->sincronizar(['jugador_id' => $jugador->id], Catalogo::clavesDe('jugador'), $deseados, $this->esPro($jugador->liga_id));
    }

    // Para mostrar el avance de los logros por cantidad (7/10 goles, racha actual...).
    public function progresoJugador(int $jugadorId): array
    {
        $jugador = $this->ficha($jugadorId);
        $partidos = $jugador ? $this->partidosJugados($jugador) : collect();

        $racha = function (callable $cumple) use ($partidos) {
            $n = 0;
            foreach ($partidos->reverse() as $p) {
                if (! $cumple($p)) {
                    break;
                }
                $n++;
            }

            return $n;
        };

        return [
            'goles' => (int) $partidos->sum('goles_jugador'),
            'partidos' => $partidos->count(),
            'partidos sin tarjeta' => $racha(fn ($p) => $p->tarjetas_jugador === 0),
            'partidos seguidos con gol' => $racha(fn ($p) => $p->goles_jugador > 0),
        ];
    }

    public function progresoPichangas(int $userId): array
    {
        return ['pichangas' => $this->pichangasJugadas($userId)->count()];
    }

    private function ficha(int $jugadorId): ?object
    {
        return DB::table('jugadores')
            ->join('equipos', 'equipos.id', '=', 'jugadores.equipo_id')
            ->where('jugadores.id', $jugadorId)
            ->first(['jugadores.id', 'jugadores.equipo_id', 'jugadores.posicion', 'equipos.liga_id']);
    }

    // Partidos finalizados de su equipo en los que consta que jugó: en la lista, convocado,
    // con gol o con tarjeta. En orden cronológico.
    private function partidosJugados(object $jugador): Collection
    {
        $id = $jugador->id;
        $existe = fn (string $tabla) => fn ($q) => $q->select(DB::raw(1))->from($tabla)
            ->whereColumn("{$tabla}.partido_id", 'p.id')->where("{$tabla}.jugador_id", $id);

        return DB::table('partidos as p')
            ->where('p.estado', 'finalizado')
            ->where(fn ($q) => $q->where('p.equipo_local_id', $jugador->equipo_id)->orWhere('p.equipo_visita_id', $jugador->equipo_id))
            ->where(fn ($q) => $q->whereExists($existe('asistencias'))
                ->orWhereExists($existe('convocatorias'))
                ->orWhereExists($existe('goles'))
                ->orWhereExists($existe('tarjetas')))
            ->select('p.id', 'p.torneo_id', 'p.equipo_local_id', 'p.goles_local', 'p.goles_visita', 'p.fecha_hora', 'p.finalizado_en', 'p.created_at')
            ->selectSub(fn ($q) => $q->from('goles')->whereColumn('goles.partido_id', 'p.id')->where('goles.jugador_id', $id)->where('goles.tipo', '<>', 'autogol')->selectRaw('COUNT(goles.id)'), 'goles_jugador')
            ->selectSub(fn ($q) => $q->from('tarjetas')->whereColumn('tarjetas.partido_id', 'p.id')->where('tarjetas.jugador_id', $id)->selectRaw('COUNT(tarjetas.id)'), 'tarjetas_jugador')
            ->selectSub(fn ($q) => $q->from('convocatorias')->whereColumn('convocatorias.partido_id', 'p.id')->where('convocatorias.jugador_id', $id)->selectRaw('MAX(convocatorias.es_arquero)'), 'es_arquero')
            ->orderByRaw('COALESCE(p.fecha_hora, p.finalizado_en, p.created_at), p.id')
            ->get()
            ->map(function ($p) use ($jugador) {
                $local = (int) $p->equipo_local_id === (int) $jugador->equipo_id;
                $contra = $local ? $p->goles_visita : $p->goles_local;

                $p->goles_jugador = (int) $p->goles_jugador;
                $p->tarjetas_jugador = (int) $p->tarjetas_jugador;
                $p->goles_contra = $contra === null ? null : (int) $contra;
                // Sin convocatoria cargada, se usa la posición de su ficha.
                $p->arquero = $p->es_arquero === null
                    ? (bool) preg_match(self::POSICION_ARQUERO, (string) $jugador->posicion)
                    : (bool) $p->es_arquero;
                $p->cuando = Carbon::parse($p->fecha_hora ?? $p->finalizado_en ?? $p->created_at);

                return $p;
            });
    }

    // ---------------------------------------------------------------- torneo

    public function recalcularTorneo(int $torneoId): void
    {
        $torneo = DB::table('torneos')->where('id', $torneoId)->first(['id', 'liga_id', 'cerrado_en']);
        if (! $torneo) {
            return;
        }

        $partidos = DB::table('partidos')->where('torneo_id', $torneoId)->get();
        $cerrado = fn (Collection $ps) => $ps->isNotEmpty()
            && $ps->every(fn ($p) => in_array($p->estado, ['finalizado', 'suspendido'], true))
            && $ps->contains('estado', 'finalizado');
        $cuando = fn (Collection $ps) => $ps->where('estado', 'finalizado')
            ->map(fn ($p) => Carbon::parse($p->fecha_hora ?? $p->finalizado_en ?? $p->updated_at))->max();

        $deseados = [];
        $otorgar = function (string $clave, int $jugadorId, Carbon $fecha, ?int $numeroFecha = null) use (&$deseados, $torneo) {
            $huella = "{$clave}|j{$jugadorId}|t{$torneo->id}".($numeroFecha !== null ? "|f{$numeroFecha}" : '');
            $deseados[$huella] = [
                'clave' => $clave, 'jugador_id' => $jugadorId, 'liga_id' => $torneo->liga_id,
                'torneo_id' => $torneo->id, 'numero_fecha' => $numeroFecha, 'obtenido_en' => $fecha,
            ];
        };

        // Figura de cada fecha ya jugada: el máximo goleador, con 2 o más y sin empate.
        foreach ($partidos->groupBy('numero_fecha') as $numero => $ps) {
            if (! $cerrado($ps)) {
                continue;
            }
            $top = DB::table('goles')
                ->whereIn('partido_id', $ps->where('estado', 'finalizado')->pluck('id'))
                ->where('tipo', '<>', 'autogol')
                ->groupBy('jugador_id')
                ->selectRaw('jugador_id, COUNT(id) AS n')
                ->orderByDesc('n')
                ->limit(2)
                ->get();

            if ($top->isNotEmpty() && $top[0]->n >= 2 && ($top->count() === 1 || $top[1]->n < $top[0]->n)) {
                $otorgar('figura_fecha', (int) $top[0]->jugador_id, $cuando($ps), (int) $numero);
            }
        }

        // Campeón, pichichi y asistencia perfecta: cuando el organizador cierra el torneo.
        if ($torneo->cerrado_en && $cerrado($partidos)) {
            $fin = $cuando($partidos);

            // Campeón: con llave, el ganador de la final. Si no, el primero de la tabla
            // (pts, diferencia, goles a favor); empate total = todos.
            $final = Partido::withoutGlobalScopes()->where('torneo_id', $torneoId)->where('ronda', 'final')->where('estado', 'finalizado')->first();
            if ($final) {
                $campeones = collect([$final->ganadorId()])->filter();
            } else {
                $tabla = Tabla::delTorneo(Torneo::withoutGlobalScopes()->findOrFail($torneoId));
                $primero = $tabla->first();
                $campeones = $primero && $primero->pj > 0
                    ? $tabla->filter(fn ($f) => $f->pts == $primero->pts && $f->dif == $primero->dif && $f->gf == $primero->gf)->pluck('equipo_id')
                    : collect();
            }
            if ($campeones->isNotEmpty()) {
                foreach (DB::table('jugadores')->whereIn('equipo_id', $campeones)->where('activo', true)->pluck('id') as $id) {
                    $otorgar('campeon', $id, $fin);
                }
            }

            // Pichichi: el (o los) de más goles del torneo.
            $goleadores = DB::table('vista_goleadores')->where('torneo_id', $torneoId)->get();
            $max = (int) $goleadores->max('goles');
            if ($max > 0) {
                foreach ($goleadores->where('goles', $max) as $g) {
                    $otorgar('pichichi', (int) $g->jugador_id, $fin);
                }
            }

            // Asistencia perfecta: en la lista de todos los partidos de su equipo (mínimo 3).
            if (DB::table('ligas')->where('id', $torneo->liga_id)->value('pedir_lista')) {
                $jugados = $partidos->where('estado', 'finalizado');
                $porEquipo = $jugados->flatMap(fn ($p) => [[$p->equipo_local_id, $p->id], [$p->equipo_visita_id, $p->id]])
                    ->groupBy(0)->map(fn ($filas) => collect($filas)->pluck(1));

                foreach ($porEquipo as $equipoId => $partidosEquipo) {
                    if ($partidosEquipo->count() < 3) {
                        continue;
                    }
                    $perfectos = DB::table('asistencias')
                        ->join('jugadores', 'jugadores.id', '=', 'asistencias.jugador_id')
                        ->where('jugadores.equipo_id', $equipoId)
                        ->whereIn('asistencias.partido_id', $partidosEquipo)
                        ->groupBy('asistencias.jugador_id')
                        ->havingRaw('COUNT(DISTINCT asistencias.partido_id) = ?', [$partidosEquipo->count()])
                        ->pluck('asistencias.jugador_id');
                    foreach ($perfectos as $id) {
                        $otorgar('asistencia_perfecta', (int) $id, $fin);
                    }
                }
            }
        }

        $this->sincronizar(['torneo_id' => $torneoId], Catalogo::clavesDe('torneo'), $deseados, $this->esPro($torneo->liga_id));
    }

    // ---------------------------------------------------------------- pichangas

    public function recalcularUsuarioPichangas(int $userId): void
    {
        $jugadas = $this->pichangasJugadas($userId);

        $deseados = [];
        $otorgar = function (string $clave, object $fila) use (&$deseados, $userId) {
            $deseados["{$clave}|u{$userId}"] ??= [
                'clave' => $clave, 'user_id' => $userId,
                'obtenido_en' => Carbon::parse(substr((string) $fila->fecha, 0, 10).' '.$fila->hora),
            ];
        };

        foreach ($jugadas->values() as $i => $fila) {
            if ($i + 1 === 10) {
                $otorgar('pichanguero_10', $fila);
            }
            if ($i + 1 === 50) {
                $otorgar('pichanguero_50', $fila);
            }
            if ($fila->goles >= 3) {
                $otorgar('pichanga_hat_trick', $fila);
            }
        }

        $this->sincronizar(['user_id' => $userId], Catalogo::clavesDe('pichanga'), $deseados, false);
    }

    private function pichangasJugadas(int $userId): Collection
    {
        return DB::table('pichanga_asistencias as a')
            ->join('pichanga_fechas as f', 'f.id', '=', 'a.pichanga_fecha_id')
            ->where('a.user_id', $userId)
            ->where('a.estado', 'confirmado')
            ->where('f.estado', 'jugada')
            ->orderBy('f.fecha')
            ->get(['f.fecha', 'f.hora', 'a.goles']);
    }

    // ---------------------------------------------------------------- comunes

    // Deja en la base exactamente los logros que se cumplen, dentro de $alcance y $claves.
    private function sincronizar(array $alcance, array $claves, array $deseados, bool $pro): void
    {
        $existentes = Logro::where($alcance)->whereIn('clave', $claves)->pluck('huella')->flip();

        foreach ($deseados as $huella => $datos) {
            if ($existentes->has($huella) || (Catalogo::esPro($datos['clave']) && ! $pro)) {
                continue;
            }
            Logro::firstOrCreate(['huella' => $huella], $datos);
        }

        Logro::where($alcance)->whereIn('clave', $claves)->whereNotIn('huella', array_keys($deseados))->delete();
    }

    private function esPro(int $ligaId): bool
    {
        return $this->ligasPro[$ligaId] ??= (bool) DB::table('ligas')->where('id', $ligaId)
            ->where('pro_hasta', '>', now())->exists();
    }
}
