<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cancha extends Model
{
    protected $table = 'canchas';

    protected $fillable = ['nombre', 'direccion'];

    public function partidos(): HasMany
    {
        return $this->hasMany(Partido::class);
    }
}
