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

    protected $fillable = ['nombre', 'slug', 'descripcion', 'logo', 'owner_id', 'activa', 'publica'];

    protected $casts = [
        'activa' => 'boolean',
        'publica' => 'boolean',
    ];

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
