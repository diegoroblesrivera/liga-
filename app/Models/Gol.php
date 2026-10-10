<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Gol extends Model
{
    protected $table = 'goles';

    protected $fillable = ['partido_id', 'jugador_id', 'equipo_id', 'minuto', 'tipo'];

    protected $casts = [
        'minuto' => 'integer',
    ];

    public function partido(): BelongsTo
    {
        return $this->belongsTo(Partido::class);
    }

    public function jugador(): BelongsTo
    {
        return $this->belongsTo(Jugador::class);
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }
}
