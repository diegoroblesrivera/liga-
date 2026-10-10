<?php

namespace App\Models;

use App\Models\Concerns\PerteneceALiga;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Equipo extends Model
{
    use PerteneceALiga;

    protected $table = 'equipos';

    protected $fillable = ['nombre', 'modalidad_id', 'escudo', 'foto', 'color_camiseta', 'activo'];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // --- Relaciones ---

    public function modalidad(): BelongsTo
    {
        return $this->belongsTo(Modalidad::class);
    }

    public function jugadores(): HasMany
    {
        return $this->hasMany(Jugador::class);
    }

    public function invitaciones(): HasMany
    {
        return $this->hasMany(Invitacion::class);
    }

    public function torneos(): BelongsToMany
    {
        return $this->belongsToMany(Torneo::class, 'equipo_torneo');
    }

    // El DT que dirige este equipo (users.equipo_id). Un DT por equipo por convención.
    public function dt(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function partidosLocal(): HasMany
    {
        return $this->hasMany(Partido::class, 'equipo_local_id');
    }

    public function partidosVisita(): HasMany
    {
        return $this->hasMany(Partido::class, 'equipo_visita_id');
    }

    // --- Cupo ---

    public function cupoMaximo(): ?int
    {
        return $this->modalidad?->max_jugadores;
    }

    public function tieneCupoDisponible(): bool
    {
        $cupo = $this->cupoMaximo();

        return $cupo === null || $this->jugadores()->count() < $cupo;
    }
}
