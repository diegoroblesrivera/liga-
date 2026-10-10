<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'equipo_id', // solo se usa cuando el usuario es DT
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // --- Relaciones ---

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    // Equipo que dirige (solo si tiene rol DT)
    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    // Ficha deportiva enlazada (solo si tiene rol jugador)
    public function jugador(): HasOne
    {
        return $this->hasOne(Jugador::class);
    }

    // Partidos donde actúa como árbitro asignado
    public function partidosArbitrados(): HasMany
    {
        return $this->hasMany(Partido::class, 'arbitro_id');
    }

    // Pichangas semanales de las que es miembro (organizador o jugador)
    public function pichangas(): BelongsToMany
    {
        return $this->belongsToMany(Pichanga::class, 'pichanga_user')
            ->withPivot('rol')
            ->withTimestamps();
    }

    // --- Helpers de rol ---

    public function hasRole(string $rol): bool
    {
        return $this->roles->contains('nombre', $rol);
    }

    public function hasAnyRole(array $roles): bool
    {
        return $this->roles->pluck('nombre')->intersect($roles)->isNotEmpty();
    }
}
