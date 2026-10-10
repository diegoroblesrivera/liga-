<?php

namespace App\Policies;

use App\Models\Gol;
use App\Models\Partido;
use App\Models\User;

class GolPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Gol $gol): bool
    {
        return true;
    }

    // Registrar un gol: admin (global) o el árbitro asignado, SOLO con el partido en juego.
    // Uso: $user->can('create', [Gol::class, $partido])
    public function create(User $user, Partido $partido): bool
    {
        return $user->hasRole('arbitro')
            && $user->id === $partido->arbitro_id
            && $partido->estaEnJuego();
    }

    // Corregir un gol: mismas condiciones (partido en juego).
    public function update(User $user, Gol $gol): bool
    {
        return $user->hasRole('arbitro')
            && $user->id === $gol->partido->arbitro_id
            && $gol->partido->estaEnJuego();
    }

    // Eliminar un gol mal cargado: solo mientras el partido siga en juego.
    // Una vez finalizado queda bloqueado y solo el admin (global) puede tocarlo.
    public function delete(User $user, Gol $gol): bool
    {
        return $user->hasRole('arbitro')
            && $user->id === $gol->partido->arbitro_id
            && $gol->partido->estaEnJuego();
    }
}
