<?php

namespace App\Policies;

use App\Models\Partido;
use App\Models\Tarjeta;
use App\Models\User;

class TarjetaPolicy
{
    /**
     * Solo el árbitro asignado puede registrar tarjetas, y solo con el
     * partido en juego. El admin pasa por Gate::before.
     */
    public function create(User $user, Partido $partido): bool
    {
        return $partido->arbitro_id === $user->id
            && $partido->estado === 'en_juego';
    }

    public function delete(User $user, Tarjeta $tarjeta): bool
    {
        return $tarjeta->partido->arbitro_id === $user->id
            && $tarjeta->partido->estado === 'en_juego';
    }
}
