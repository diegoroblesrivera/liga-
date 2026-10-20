<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\LigaActual;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'equipo_id', // solo se usa cuando el usuario es DT
        'run',       // datos para la portería (pichangas)
        'patente',
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
            'ultimo_acceso' => 'datetime',
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

    // Personas que lo acompañan a las pichangas (para la lista de acceso)
    public function acompanantes(): HasMany
    {
        return $this->hasMany(Acompanante::class)->orderBy('id');
    }

    // Campeonatos donde es owner / admin / árbitro (los de DT y jugador salen de su equipo).
    public function ligas(): BelongsToMany
    {
        return $this->belongsToMany(Liga::class, 'liga_user')->withPivot('rol')->withTimestamps();
    }

    // --- Campeonatos ---

    // Cachés del request (liga_user y campeonatos accesibles).
    protected ?Collection $cacheRolesLiga = null;
    protected ?array $cacheLigasIds = null;

    // Filas de liga_user del usuario: [{liga_id, rol}, ...]
    public function rolesEnLigas(): Collection
    {
        return $this->cacheRolesLiga ??= DB::table('liga_user')
            ->where('user_id', $this->id)
            ->get(['liga_id', 'rol']);
    }

    // ¿Tiene alguno de estos roles en el campeonato indicado (o el actual)?
    public function rolEnLiga(array $roles, ?int $ligaId = null): bool
    {
        $ligaId ??= LigaActual::id();

        return $ligaId !== null
            && $this->rolesEnLigas()->where('liga_id', $ligaId)->whereIn('rol', $roles)->isNotEmpty();
    }

    // Dueño o admin del campeonato. El super admin lo es de todos.
    public function esAdminDeLiga(?int $ligaId = null): bool
    {
        return (bool) $this->is_super_admin || $this->rolEnLiga(['owner', 'admin'], $ligaId);
    }

    // IDs de los campeonatos donde participa: por rol (liga_user), como DT o como jugador.
    public function ligasIds(): array
    {
        if ($this->cacheLigasIds !== null) {
            return $this->cacheLigasIds;
        }

        if ($this->is_super_admin) {
            return $this->cacheLigasIds = Liga::orderBy('id')->pluck('id')->all();
        }

        $ids = $this->rolesEnLigas()->pluck('liga_id');

        if ($this->equipo_id) {
            $ids->push(Equipo::withoutGlobalScopes()->whereKey($this->equipo_id)->value('liga_id'));
        }

        $ids = $ids->merge(
            Equipo::withoutGlobalScopes()
                ->whereIn('id', Jugador::withoutGlobalScopes()->where('user_id', $this->id)->select('equipo_id'))
                ->pluck('liga_id')
        );

        return $this->cacheLigasIds = $ids->filter()->unique()->sort()->values()->all();
    }

    public function olvidarCacheLigas(): void
    {
        $this->cacheRolesLiga = null;
        $this->cacheLigasIds = null;
    }

    // --- Helpers de rol ---

    // ¿Participa en algún campeonato? Quien solo juega pichangas no.
    public function participaEnTorneos(): bool
    {
        return $this->ligasIds() !== [];
    }

    // 'admin' y 'arbitro' son por campeonato (el actual); 'dt' y 'jugador' siguen siendo
    // roles globales y los permisos finos los dan las policies (su equipo, su partido).
    public function hasRole(string $rol): bool
    {
        return match ($rol) {
            'admin' => $this->esAdminDeLiga(),
            'arbitro' => $this->rolEnLiga(['arbitro']),
            default => $this->roles->contains('nombre', $rol),
        };
    }

    public function hasAnyRole(array $roles): bool
    {
        return collect($roles)->contains(fn ($rol) => $this->hasRole($rol));
    }
}
