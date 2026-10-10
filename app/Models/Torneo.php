<?php

namespace App\Models;

use App\Models\Concerns\PerteneceALiga;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Torneo extends Model
{
    use PerteneceALiga;

    protected $table = 'torneos';

    protected $fillable = ['nombre', 'temporada', 'fecha_inicio', 'fecha_fin'];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    public function partidos(): HasMany
    {
        return $this->hasMany(Partido::class);
    }

    public function equipos(): BelongsToMany
    {
        return $this->belongsToMany(Equipo::class, 'equipo_torneo');
    }
}
