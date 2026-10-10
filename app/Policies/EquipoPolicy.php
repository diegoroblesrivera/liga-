<?php

namespace App\Policies;

use App\Models\Equipo;
use App\Models\User;

class EquipoPolicy
{
    // Ver listado y detalle: cualquier usuario autenticado.
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Equipo $equipo): bool
    {
        return true;
    }

    // Crear equipos: solo admin (lo concede el Gate::before global).
    public function create(User $user): bool
    {
        return false;
    }

    // Editar: admin (global) o el DT que dirige ese equipo.
    public function update(User $user, Equipo $equipo): bool
    {
        return $user->hasRole('dt') && $user->equipo_id === $equipo->id;
    }

    // Borrar: solo admin.
    public function delete(User $user, Equipo $equipo): bool
    {
        return false;
    }
}
