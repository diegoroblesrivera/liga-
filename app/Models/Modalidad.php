<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Modalidad extends Model
{
    protected $table = 'modalidades';

    protected $fillable = ['nombre', 'max_jugadores', 'jugadores_en_cancha'];

    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class);
    }
}
