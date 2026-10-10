<?php

namespace App\Models;

use App\Models\Concerns\FiltradoPorLigaVia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Convocatoria extends Model
{
    use FiltradoPorLigaVia;

    // Pertenece al campeonato a través de su partido.
    protected static array $ligaVia = ['partido_id', Partido::class];

    protected $table = 'convocatorias';

    protected $fillable = ['partido_id', 'jugador_id', 'es_titular', 'es_arquero'];

    protected $casts = [
        'es_titular' => 'boolean',
        'es_arquero' => 'boolean',
    ];

    public function partido(): BelongsTo
    {
        return $this->belongsTo(Partido::class);
    }

    public function jugador(): BelongsTo
    {
        return $this->belongsTo(Jugador::class);
    }
}
