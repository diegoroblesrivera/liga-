<?php

namespace App\Policies;

use App\Models\Equipo;
use App\Models\Invitacion;
use App\Models\User;

class InvitacionPolicy
{
    // Ver invitaciones: admin (global) o un DT (las suyas).
    public function viewAny(User $user): bool
    {
        return $user->hasRole('dt');
    }

    public function view(User $user, Invitacion $invitacion): bool
    {
        return $user->hasRole('dt') && $user->equipo_id === $invitacion->equipo_id;
    }

    // Crear invitación. La de tipo 'dt' la genera solo el admin (global);
    // la de tipo 'jugador' la genera el DT de ese equipo (o el admin).
    // Uso: $user->can('create', [Invitacion::class, $equipo, 'jugador'])
    public function create(User $user, Equipo $equipo, string $tipo): bool
    {
        if ($tipo === 'dt') {
            return false; // solo admin, vía Gate::before
        }

        // tipo 'jugador'
        return $user->hasRole('dt') && $user->equipo_id === $equipo->id;
    }

    // Revocar: admin (global) o el DT del equipo (solo sus links de jugador).
    public function delete(User $user, Invitacion $invitacion): bool
    {
        return $user->hasRole('dt')
            && $user->equipo_id === $invitacion->equipo_id
            && $invitacion->tipo === 'jugador';
    }
}
