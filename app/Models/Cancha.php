<?php

namespace App\Models;

use App\Models\Concerns\PerteneceALiga;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cancha extends Model
{
    use PerteneceALiga;

    protected $table = 'canchas';

    protected $fillable = ['nombre', 'direccion'];

    public function partidos(): HasMany
    {
        return $this->hasMany(Partido::class);
    }
}
