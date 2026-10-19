<?php

namespace App\Http\Controllers;

use App\Models\Gol;
use App\Models\Jugador;
use App\Models\Partido;
use App\Support\Lista;
use Illuminate\Http\Request;

class GolController extends Controller
{
    // Registrar un gol. Solo el árbitro asignado y con el partido EN JUEGO
    // (esa condición vive en GolPolicy@create; aquí solo se autoriza).
    public function store(Request $request, Partido $partido)
    {
        $this->authorize('create', [Gol::class, $partido]);

        $data = $request->validate([
            'jugador_id' => ['required', 'exists:jugadores,id'],
            'minuto' => ['nullable', 'integer', 'min:0', 'max:200'],
            'tipo' => ['required', 'in:normal,penal,autogol'],
        ]);

        $jugador = Jugador::findOrFail($data['jugador_id']);

        // El jugador debe pertenecer a uno de los dos equipos del partido.
        abort_unless(
            in_array($jugador->equipo_id, [$partido->equipo_local_id, $partido->equipo_visita_id], true),
            422,
            'El jugador no pertenece a este partido.'
        );

        if (Lista::bloquea($partido, $jugador)) {
            return back()->withErrors(['jugador_id' => "{$jugador->nombre} no está en la lista de asistencia: sin lista no puede jugar."]);
        }

        // Normal/penal suma al equipo del jugador; el autogol suma al rival.
        $equipoId = $data['tipo'] === 'autogol'
            ? ($jugador->equipo_id === $partido->equipo_local_id ? $partido->equipo_visita_id : $partido->equipo_local_id)
            : $jugador->equipo_id;

        $partido->goles()->create([
            'jugador_id' => $jugador->id,
            'equipo_id' => $equipoId,
            'minuto' => $data['minuto'] ?? null,
            'tipo' => $data['tipo'],
        ]);

        return back()->with('status', 'Gol registrado.');
    }

    // Borrar un gol mal cargado. Solo mientras el partido siga en juego
    // (condición en GolPolicy@delete).
    public function destroy(Gol $gol)
    {
        $this->authorize('delete', $gol);

        $gol->delete();

        return back()->with('status', 'Gol eliminado.');
    }
}
