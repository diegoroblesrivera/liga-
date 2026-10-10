<?php

namespace App\Models\Concerns;

use App\Models\Liga;
use App\Support\LigaActual;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Para modelos con columna liga_id (torneos, canchas, equipos, invitaciones):
 * - todas las consultas se filtran por el campeonato actual;
 * - al crear, toman el campeonato actual si no traen liga_id.
 */
trait PerteneceALiga
{
    public static function bootPerteneceALiga(): void
    {
        static::addGlobalScope('liga', function (Builder $query) {
            if ($ligaId = LigaActual::id()) {
                $query->where($query->getModel()->qualifyColumn('liga_id'), $ligaId);
            }
        });

        static::creating(function ($model) {
            if (! $model->liga_id && LigaActual::id()) {
                $model->liga_id = LigaActual::id();
            }
        });
    }

    public function liga(): BelongsTo
    {
        return $this->belongsTo(Liga::class);
    }
}
