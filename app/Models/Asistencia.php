<?php

namespace App\Models;

use App\Models\Concerns\FiltradoPorLigaVia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Jugador presente en un partido: por QR (él mismo) o manual (DT, árbitro u organizador).
class Asistencia extends Model
{
    use FiltradoPorLigaVia;

    protected static array $ligaVia = ['partido_id', Partido::class];

    protected $fillable = ['partido_id', 'jugador_id', 'metodo', 'marcado_por'];

    public function partido(): BelongsTo
    {
        return $this->belongsTo(Partido::class);
    }

    public function jugador(): BelongsTo
    {
        return $this->belongsTo(Jugador::class);
    }

    public function marcador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marcado_por');
    }
}
