<?php

namespace App\Support\Torneos;

use App\Models\Etapa;
use App\Models\Partido;
use App\Models\Torneo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Formatos de torneo:
 *   1 etapa:  liga (ida o ida y vuelta) · eliminación directa (8 o 16 equipos)
 *   2 etapas: liga → eliminación de los N primeros · grupos → play offs
 * La 1ª etapa se genera al crear el torneo; la 2ª, cuando termina la 1ª. En eliminación,
 * cada ronda se crea cuando se juega la anterior (gana el que hace más goles o, si
 * empatan, el que gana a penales).
 */
class Formato
{
    /**
     * @param  array  $config  nombre, temporada, etapa1 [tipo, ida_vuelta, grupos, clasifican, tercer_lugar],
     *                         etapa2 (opcional) [clasifican, tercer_lugar], programacion [dias, por_dia, hora], inicio
     * @param  array<int>  $equipos  participantes de la 1ª etapa
     */
    public static function crear(array $config, array $equipos): Torneo
    {
        return DB::transaction(function () use ($config, $equipos) {
            $inicio = Carbon::parse($config['inicio'])->startOfDay();

            $torneo = Torneo::create([
                'nombre' => $config['nombre'],
                'temporada' => $config['temporada'] ?? null,
                'fecha_inicio' => $inicio->toDateString(),
            ]);
            $torneo->forceFill(['programacion' => $config['programacion']])->save();
            $torneo->equipos()->attach($equipos);

            $e1 = $config['etapa1'];
            $etapa1 = $torneo->etapas()->create([
                'orden' => 1,
                'tipo' => $e1['tipo'],
                'ida_vuelta' => (bool) ($e1['ida_vuelta'] ?? false),
                'grupos' => $e1['grupos'] ?? null,
                'clasifican' => $e1['clasifican'] ?? null,
                'tercer_lugar' => (bool) ($e1['tercer_lugar'] ?? false),
            ]);

            if (! empty($config['etapa2'])) {
                $torneo->etapas()->create([
                    'orden' => 2,
                    'tipo' => 'eliminacion',
                    'clasifican' => $config['etapa2']['clasifican'],
                    'tercer_lugar' => (bool) ($config['etapa2']['tercer_lugar'] ?? false),
                ]);
            }

            // Sorteo: el orden de los equipos define cruces y grupos.
            shuffle($equipos);

            match ($etapa1->tipo) {
                'liga' => self::generarLiga($etapa1, $equipos, $inicio),
                'grupos' => self::generarGrupos($etapa1, $equipos, $inicio),
                'eliminacion' => self::generarLlave($etapa1, self::cruces($equipos), $inicio),
            };

            return $torneo;
        });
    }

    // ¿Se puede generar ya la 2ª etapa? (existe, no está generada y la 1ª terminó)
    public static function segundaPendiente(Torneo $torneo): ?Etapa
    {
        $etapas = $torneo->etapas()->get();
        $segunda = $etapas->firstWhere('orden', 2);

        return $segunda && ! $segunda->generada_en && $etapas->firstWhere('orden', 1)?->terminada() ? $segunda : null;
    }

    // Clasificados de la 1ª etapa → llave de la 2ª, cruzados por ubicación.
    public static function generarSegunda(Torneo $torneo): Etapa
    {
        $segunda = self::segundaPendiente($torneo) ?? throw new RuntimeException('La primera etapa aún no termina.');
        $primera = $torneo->etapas()->where('orden', 1)->firstOrFail();

        return DB::transaction(function () use ($primera, $segunda) {
            $cruces = $primera->tipo === 'grupos'
                ? self::crucesDeGrupos(Tabla::porGrupo($primera), $primera->clasifican)
                : self::cruces(Tabla::deEtapa($primera)->take($segunda->clasifican)->pluck('equipo_id')->all(), sembrado: true);

            self::generarLlave($segunda, $cruces, self::despuesDe($primera));

            return $segunda;
        });
    }

    // Después de cada partido de llave finalizado: si la ronda está completa, crea la siguiente.
    public static function avanzar(Etapa $etapa): void
    {
        DB::transaction(function () use ($etapa) {
            $partidos = Partido::withoutGlobalScopes()->where('etapa_id', $etapa->id)->lockForUpdate()->get();
            $rondas = $partidos->whereNotIn('ronda', ['tercer_lugar'])->groupBy('ronda');
            $orden = array_keys(\App\Models\Partido::RONDAS);

            // La ronda más avanzada que existe.
            $actual = collect($rondas->keys())->sortBy(fn ($r) => array_search($r, $orden, true))->last();
            if (! $actual || $actual === 'final') {
                return;
            }

            $ronda = $rondas[$actual]->sortBy('llave')->values();
            if ($ronda->contains(fn ($p) => $p->ganadorId() === null)) {
                return; // faltan partidos por jugar (o por definir a penales)
            }

            $siguiente = Generador::ronda($ronda->count());
            $cruces = $ronda->chunk(2)->map(fn ($par) => [$par->first()->ganadorId(), $par->last()->ganadorId()])->values()->all();
            $extra = [];
            if ($actual === 'semifinal' && $etapa->tercer_lugar) {
                $extra = [[$ronda[0]->perdedorId(), $ronda[1]->perdedorId()]];
            }

            self::crearRonda($etapa, $siguiente, $cruces, self::despuesDe($etapa), $extra);
        });
    }

    // ------------------------------------------------------------------ armado

    private static function generarLiga(Etapa $etapa, array $equipos, Carbon $inicio): void
    {
        $etapa->equipos()->attach($equipos);
        $jornadas = array_map(fn ($j) => array_map(fn ($p) => [$p[0], $p[1], null], $j), Generador::roundRobin($equipos, $etapa->ida_vuelta));
        self::guardarJornadas($etapa, $jornadas, $inicio);
    }

    private static function generarGrupos(Etapa $etapa, array $equipos, Carbon $inicio): void
    {
        $grupos = Generador::grupos($equipos, $etapa->grupos);
        foreach ($grupos as $letra => $miembros) {
            foreach ($miembros as $id) {
                $etapa->equipos()->attach($id, ['grupo' => $letra]);
            }
        }
        self::guardarJornadas($etapa, Generador::jornadasDeGrupos($grupos, $etapa->ida_vuelta), $inicio);
    }

    // Primera ronda de una llave a partir de los cruces [[a, b], ...] en orden de llave.
    private static function generarLlave(Etapa $etapa, array $cruces, Carbon $inicio): void
    {
        foreach ($cruces as $i => [$a, $b]) {
            $etapa->equipos()->syncWithoutDetaching([$a => ['siembra' => $i * 2 + 1], $b => ['siembra' => $i * 2 + 2]]);
        }
        self::crearRonda($etapa, Generador::ronda(count($cruces) * 2), $cruces, $inicio);
    }

    private static function crearRonda(Etapa $etapa, string $ronda, array $cruces, Carbon $inicio, array $tercerLugar = []): void
    {
        $partidos = array_map(fn ($c, $i) => [$c[0], $c[1], $ronda, $i + 1], $cruces, array_keys($cruces));
        foreach ($tercerLugar as $c) {
            array_unshift($partidos, [$c[0], $c[1], 'tercer_lugar', 1]); // antes que la final
        }
        $numero = (int) Partido::withoutGlobalScopes()->where('torneo_id', $etapa->torneo_id)->max('numero_fecha') + 1;
        $torneo = Torneo::withoutGlobalScopes()->findOrFail($etapa->torneo_id);

        foreach (Generador::programar([$partidos], $inicio, $torneo->programacion ?? []) as $item) {
            [$local, $visita, $r, $llave] = $item['partido'];
            Partido::create([
                'torneo_id' => $etapa->torneo_id, 'etapa_id' => $etapa->id, 'ronda' => $r, 'llave' => $llave,
                'numero_fecha' => $numero, 'equipo_local_id' => $local, 'equipo_visita_id' => $visita,
                'fecha_hora' => $item['fecha'], 'estado' => 'programado',
            ]);
        }

        $etapa->forceFill(['generada_en' => $etapa->generada_en ?? now()])->save();
    }

    private static function guardarJornadas(Etapa $etapa, array $jornadas, Carbon $inicio): void
    {
        $torneo = Torneo::withoutGlobalScopes()->findOrFail($etapa->torneo_id);
        $base = (int) Partido::withoutGlobalScopes()->where('torneo_id', $etapa->torneo_id)->max('numero_fecha');

        foreach (Generador::programar($jornadas, $inicio, $torneo->programacion ?? []) as $item) {
            [$local, $visita, $grupo] = $item['partido'];
            Partido::create([
                'torneo_id' => $etapa->torneo_id, 'etapa_id' => $etapa->id, 'grupo' => $grupo,
                'numero_fecha' => $base + $item['jornada'] + 1, 'equipo_local_id' => $local, 'equipo_visita_id' => $visita,
                'fecha_hora' => $item['fecha'], 'estado' => 'programado',
            ]);
        }

        $etapa->forceFill(['generada_en' => now()])->save();
    }

    // ------------------------------------------------------------------ cruces

    // Equipos en orden: sorteados (cruces consecutivos) o sembrados (1 vs N, 2 vs N-1...).
    public static function cruces(array $equipos, bool $sembrado = false): array
    {
        $equipos = array_values($equipos);
        if ($sembrado) {
            $equipos = array_map(fn ($s) => $equipos[$s - 1], Generador::siembra(count($equipos)));
        }

        return array_chunk($equipos, 2);
    }

    // Grupos → llave. Con 2 por grupo: 1°A-2°B, 1°C-2°D... arriba y 1°B-2°A, 1°D-2°C... abajo,
    // así los del mismo grupo solo pueden volver a cruzarse en la final. Si no, por ubicación.
    public static function crucesDeGrupos(Collection $tablas, int $porGrupo): array
    {
        $letras = $tablas->keys()->values();
        $pos = fn ($letra, $i) => $tablas[$letra][$i]->equipo_id;

        if ($porGrupo === 2 && $letras->count() % 2 === 0) {
            $arriba = $abajo = [];
            foreach ($letras->chunk(2) as $par) {
                [$x, $y] = $par->values()->all();
                $arriba[] = [$pos($x, 0), $pos($y, 1)];
                $abajo[] = [$pos($y, 0), $pos($x, 1)];
            }

            return array_merge($arriba, $abajo);
        }

        // Ranking general: primero todos los 1°, luego los 2°... cada nivel por rendimiento.
        $ranking = collect(range(0, $porGrupo - 1))->flatMap(fn ($i) => $letras->map(fn ($l) => $tablas[$l][$i])
            ->sortBy([['pts', 'desc'], ['dif', 'desc'], ['gf', 'desc']])->values())->pluck('equipo_id')->all();

        return self::cruces($ranking, sembrado: true);
    }

    // La etapa o ronda siguiente parte el día después del último partido.
    private static function despuesDe(Etapa $etapa): Carbon
    {
        $ultimo = Partido::withoutGlobalScopes()->where('etapa_id', $etapa->id)->max('fecha_hora');

        return $ultimo ? Carbon::parse($ultimo)->addDay()->startOfDay() : today();
    }
}
