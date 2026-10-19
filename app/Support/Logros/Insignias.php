<?php

namespace App\Support\Logros;

use App\Models\Jugador;
use App\Models\Logro;
use App\Models\User;
use Illuminate\Support\Collection;

// Lectura de logros para las vistas: la grilla del perfil, las mejores junto al nombre
// y el aviso de "¡Desbloqueaste…!".
class Insignias
{
    private const PESO_NIVEL = ['oro' => 3, 'plata' => 2, 'bronce' => 1];

    /**
     * Grilla de un conjunto de logros: cada insignia del catálogo con lo obtenido
     * (primera vez y cuántas veces), el avance y si está bloqueada por no ser Pro.
     */
    public static function grilla(Collection $obtenidos, array $claves, array $progreso, bool $ligaPro): Collection
    {
        $porClave = $obtenidos->groupBy('clave');

        return collect($claves)->map(function (string $clave) use ($porClave, $progreso, $ligaPro) {
            $def = Catalogo::get($clave);
            $logros = $porClave->get($clave, collect())->sortBy('obtenido_en')->values();
            $actual = isset($def['medida']) ? (int) ($progreso[$def['medida']] ?? 0) : null;

            return $def + [
                'obtenido' => $logros->first(),
                'veces' => $logros->count(),
                'avance' => $actual !== null && $logros->isEmpty()
                    ? ['actual' => min($actual, $def['meta']), 'meta' => $def['meta'], 'texto' => min($actual, $def['meta']).'/'.$def['meta'].' '.$def['medida']]
                    : null,
                'bloqueado_pro' => $def['pro'] && ! $ligaPro && $logros->isEmpty(),
            ];
        })
            // Primero los obtenidos (los mejores arriba); después los que faltan.
            ->sortBy(fn ($i) => [$i['obtenido'] ? 0 : 1, $i['obtenido'] ? -self::PESO_NIVEL[$i['nivel']] : 0])
            ->values();
    }

    /** @return Collection<int, Collection> jugador_id => las $n mejores insignias (sin repetir) */
    public static function mejores(iterable $jugadorIds, int $n = 3): Collection
    {
        $ids = collect($jugadorIds)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        return Logro::whereIn('jugador_id', $ids)->get(['jugador_id', 'clave'])
            ->groupBy('jugador_id')
            ->map(fn ($logros) => $logros->pluck('clave')->unique()
                ->map(fn ($c) => Catalogo::get($c))
                ->sortByDesc(fn ($d) => [self::PESO_NIVEL[$d['nivel']], $d['pro'] ? 1 : 0])
                ->take($n)
                ->values());
    }

    // Logros del usuario aún no avisados (de sus fichas y de sus pichangas). Los marca vistos.
    public static function tomarNuevos(User $user): Collection
    {
        $fichas = Jugador::withoutGlobalScopes()->where('user_id', $user->id)->pluck('id');

        $nuevos = Logro::whereNull('visto_en')
            ->where(fn ($q) => $q->where('user_id', $user->id)->orWhereIn('jugador_id', $fichas))
            ->orderBy('obtenido_en')
            ->get();

        if ($nuevos->isNotEmpty()) {
            Logro::whereIn('id', $nuevos->pluck('id'))->update(['visto_en' => now()]);
        }

        return $nuevos;
    }
}
