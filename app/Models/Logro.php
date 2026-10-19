<?php

namespace App\Models;

use App\Support\Logros\Catalogo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Insignia obtenida. Sin scope de liga: se lee por jugador o por usuario.
class Logro extends Model
{
    protected $fillable = [
        'clave', 'jugador_id', 'user_id', 'liga_id', 'torneo_id', 'numero_fecha',
        'partido_id', 'huella', 'obtenido_en', 'visto_en',
    ];

    protected $casts = [
        'obtenido_en' => 'datetime',
        'visto_en' => 'datetime',
        'numero_fecha' => 'integer',
    ];

    public function jugador(): BelongsTo
    {
        return $this->belongsTo(Jugador::class)->withoutGlobalScopes();
    }

    public function torneo(): BelongsTo
    {
        return $this->belongsTo(Torneo::class)->withoutGlobalScopes();
    }

    public function partido(): BelongsTo
    {
        return $this->belongsTo(Partido::class)->withoutGlobalScopes();
    }

    // Datos de la insignia: nombre, icono, descripción, si es Pro...
    public function definicion(): array
    {
        return Catalogo::get($this->clave);
    }
}
