<?php

namespace App\Http\Controllers;

use App\Models\Jugador;
use App\Models\Partido;
use App\Models\Tarjeta;
use Illuminate\Http\Request;

class TarjetaController extends Controller
{
    public function store(Request $request, Partido $partido)
    {
        $this->authorize('create', [Tarjeta::class, $partido]);

        $data = $request->validate([
            'jugador_id' => ['required', 'integer', 'exists:jugadores,id'],
            'tipo' => ['required', 'in:amarilla,roja'],
            'minuto' => ['nullable', 'integer', 'min:0', 'max:200'],
            'comentario' => ['nullable', 'string', 'max:200'],
        ]);

        $jugador = Jugador::findOrFail($data['jugador_id']);

        abort_unless(
            in_array($jugador->equipo_id, [$partido->equipo_local_id, $partido->equipo_visita_id]),
            422,
            'El jugador no pertenece a ninguno de los dos equipos del partido.'
        );

        Tarjeta::create([
            'partido_id' => $partido->id,
            'jugador_id' => $jugador->id,
            'equipo_id' => $jugador->equipo_id,
            'tipo' => $data['tipo'],
            'minuto' => $data['minuto'] ?? null,
            'comentario' => $data['comentario'] ?? null,
        ]);

        return back()->with('status', 'Tarjeta registrada.');
    }

    public function destroy(Tarjeta $tarjeta)
    {
        $this->authorize('delete', $tarjeta);

        $tarjeta->delete();

        return back()->with('status', 'Tarjeta eliminada.');
    }
}
