<?php

namespace App\Models\Concerns;

use App\Support\LigaActual;
use Illuminate\Database\Eloquent\Builder;

/**
 * Para modelos sin liga_id propia que pertenecen al campeonato a través de otro modelo
 * (partido → torneo, jugador → equipo, gol → partido...). El modelo declara:
 *     protected static array $ligaVia = ['torneo_id', Torneo::class];
 * y sus consultas quedan limitadas a los registros cuyo padre es del campeonato actual.
 */
trait FiltradoPorLigaVia
{
    public static function bootFiltradoPorLigaVia(): void
    {
        static::addGlobalScope('liga', function (Builder $query) {
            if (LigaActual::id()) {
                [$columna, $padre] = static::$ligaVia;

                // $padre::select('id') ya viene filtrado por la liga actual.
                $query->whereIn($query->getModel()->qualifyColumn($columna), $padre::select('id'));
            }
        });
    }
}
