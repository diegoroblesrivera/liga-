<?php

namespace App\Policies;

use App\Models\Partido;
use App\Models\User;

class PartidoPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Partido $partido): bool
    {
        return true;
    }

    // Programar el fixture: solo admin (global).
    public function create(User $user): bool
    {
        return false;
    }

    // Reprogramar / editar datos del partido: solo admin (global).
    // El admin puede hacerlo incluso con el partido finalizado.
    public function update(User $user, Partido $partido): bool
    {
        return false;
    }

    public function delete(User $user, Partido $partido): bool
    {
        return false;
    }

    // Iniciar: admin (global) o el árbitro asignado, si está programado.
    public function iniciar(User $user, Partido $partido): bool
    {
        return $user->hasRole('arbitro')
            && $user->id === $partido->arbitro_id
            && $partido->estaProgramado();
    }

    // Finalizar: admin (global) o el árbitro asignado, si está en juego.
    // Al finalizar, el partido queda bloqueado para el árbitro (ver GolPolicy).
    public function finalizar(User $user, Partido $partido): bool
    {
        return $user->hasRole('arbitro')
            && $user->id === $partido->arbitro_id
            && $partido->estaEnJuego();
    }

    // Suspender: admin (global) o el árbitro asignado, mientras no esté finalizado.
    public function suspender(User $user, Partido $partido): bool
    {
        return $user->hasRole('arbitro')
            && $user->id === $partido->arbitro_id
            && ($partido->estaProgramado() || $partido->estaEnJuego());
    }
}
