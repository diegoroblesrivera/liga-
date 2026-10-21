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

    // Regla: un jugador está en un solo equipo por campeonato. Devuelve el equipo donde
    // ya juega esa persona en el campeonato (o null), ignorando la ficha $excepto.
    public static function equipoEnLiga(int $userId, int $ligaId, ?int $excepto = null): ?Equipo
    {
        $ficha = static::withoutGlobalScopes()
            ->join('equipos', 'equipos.id', '=', 'jugadores.equipo_id')
            ->where('jugadores.user_id', $userId)
            ->where('equipos.liga_id', $ligaId)
            ->when($excepto, fn ($q) => $q->where('jugadores.id', '<>', $excepto))
            ->select('jugadores.equipo_id')
            ->first();

        return $ficha ? Equipo::withoutGlobalScopes()->find($ficha->equipo_id) : null;
    }

    public static function mensajeOtroEquipo(Equipo $equipo): string
    {
        return "Ya juegas en {$equipo->nombre} en este campeonato. Un jugador solo puede estar en un equipo por campeonato: si te cambias, pídele al organizador que te mueva.";
    }
}
