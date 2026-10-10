<?php

namespace App\Models;

use App\Models\Concerns\FiltradoPorLigaVia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tarjeta extends Model
{
    use FiltradoPorLigaVia;

    // Pertenece al campeonato a través de su partido.
    protected static array $ligaVia = ['partido_id', Partido::class];

    protected $table = 'tarjetas';

    protected $fillable = ['partido_id', 'jugador_id', 'equipo_id', 'tipo', 'minuto', 'comentario'];

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
