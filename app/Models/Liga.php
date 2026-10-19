<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

// Un campeonato: agrupa sus torneos/temporadas, equipos, canchas y árbitros.
class Liga extends Model
{
    protected $table = 'ligas';

    protected $fillable = ['nombre', 'slug', 'descripcion', 'logo', 'owner_id', 'activa', 'publica', 'pro_hasta', 'pedir_lista', 'lista_obligatoria'];

    protected $casts = [
        'activa' => 'boolean',
        'publica' => 'boolean',
        'pro_hasta' => 'datetime',
        'pedir_lista' => 'boolean',
        'lista_obligatoria' => 'boolean',
    ];

    // Lista de asistencia activada y obligatoria: sin asistencia no se juega.
    public function exigeLista(): bool
    {
        return $this->pedir_lista && $this->lista_obligatoria;
    }

    // Plan Gratis: hasta 8 equipos, todas las funciones. Pro: equipos ilimitados.
    public const EQUIPOS_PLAN_GRATIS = 8;

    // --- Plan ---

    public function esPro(): bool
    {
        return $this->pro_hasta !== null && $this->pro_hasta->isFuture();
    }

    // null = sin límite.
    public function limiteEquipos(): ?int
    {
        return $this->esPro() ? null : self::EQUIPOS_PLAN_GRATIS;
    }

    public function puedeAgregarEquipo(): bool
    {
        $limite = $this->limiteEquipos();

        return $limite === null || $this->equipos()->count() < $limite;
    }

    // Activa (o extiende) el Pro por un año. Si aún está vigente, el año se suma al final.
    // Devuelve [desde, hasta] del período cubierto.
    public function extenderProUnAnio(): array
    {
        $desde = $this->esPro() ? $this->pro_hasta->copy() : now();
        $hasta = $desde->copy()->addYear();

        $this->forceFill(['pro_hasta' => $hasta])->save();

        return [$desde, $hasta];
    }

    public function pagosPro(): HasMany
    {
        return $this->hasMany(PagoPro::class)->latest();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    // owner / admin / arbitro del campeonato (DT y jugadores se asocian por su equipo).
    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'liga_user')->withPivot('rol')->withTimestamps();
    }

    public function arbitros(): BelongsToMany
    {
        return $this->usuarios()->wherePivot('rol', 'arbitro');
    }

    // Sin el filtro por liga actual: la relación ya acota a esta liga.
    public function torneos(): HasMany
    {
        return $this->hasMany(Torneo::class)->withoutGlobalScope('liga');
    }

    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class)->withoutGlobalScope('liga');
    }

    // Slug único a partir del nombre: "Liga del Barrio" → "liga-del-barrio", "liga-del-barrio-2"...
    public static function slugDisponible(string $nombre): string
    {
        $base = Str::slug($nombre) ?: 'campeonato';
        $slug = $base;
        $n = 2;

        while (self::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }
}
