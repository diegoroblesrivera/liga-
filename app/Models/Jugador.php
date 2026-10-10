<?php

namespace App\Models;

use App\Models\Concerns\FiltradoPorLigaVia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jugador extends Model
{
    use FiltradoPorLigaVia;

    // Pertenece al campeonato a través de su equipo.
    protected static array $ligaVia = ['equipo_id', Equipo::class];

    protected $table = 'jugadores';

    protected $fillable = ['equipo_id', 'user_id', 'nombre', 'foto', 'dorsal', 'posicion', 'activo'];

    protected $casts = [
        'activo' => 'boolean',
        'dorsal' => 'integer',
    ];

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function goles(): HasMany
    {
        return $this->hasMany(Gol::class);
    }
}
