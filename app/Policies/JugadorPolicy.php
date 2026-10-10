<?php

namespace App\Policies;

use App\Models\Equipo;
use App\Models\Jugador;
use App\Models\User;

class JugadorPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Jugador $jugador): bool
    {
        return true;
    }

    // Alta manual de un jugador en un equipo: admin (global) o el DT de ese equipo.
    // La auto-inscripción por link se autoriza con el token, no con esta policy.
    // Uso: $user->can('create', [Jugador::class, $equipo])
    public function create(User $user, Equipo $equipo): bool
    {
        return $user->hasRole('dt') && $user->equipo_id === $equipo->id;
    }

    // Editar: admin (global), el DT del equipo del jugador, o el propio jugador.
    public function update(User $user, Jugador $jugador): bool
    {
        if ($user->hasRole('dt') && $user->equipo_id === $jugador->equipo_id) {
            return true;
        }

        // El jugador puede editar sus propios datos (incluido el email en su User).
        return $jugador->user_id === $user->id;
    }

    // Baja: admin (global) o el DT del equipo del jugador.
    public function delete(User $user, Jugador $jugador): bool
    {
        return $user->hasRole('dt') && $user->equipo_id === $jugador->equipo_id;
    }
}
