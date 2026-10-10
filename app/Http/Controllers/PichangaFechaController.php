<?php

namespace App\Http\Controllers;

use App\Models\PichangaFecha;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PichangaFechaController extends Controller
{
    // Detalle de una fecha: equipos, pagos y resultado (editable por el organizador).
    public function show(Request $request, PichangaFecha $fecha)
    {
        $pichanga = $fecha->pichanga;
        abort_unless($pichanga->esMiembro($request->user()), 403);

        $fecha->load('asistencias.user');
        $esOrganizador = $pichanga->esOrganizador($request->user());

        return view('pichangas.fecha', compact('pichanga', 'fecha', 'esOrganizador'));
    }

    public function voy(Request $request, PichangaFecha $fecha)
    {
        abort_unless($fecha->pichanga->esMiembro($request->user()), 403);

        if (! $fecha->estaAbierta()) {
            return back()->withErrors(['fecha' => 'Las inscripciones de esta fecha están cerradas.']);
        }

        $asistencia = $fecha->anotar($request->user());

        return back()->with('status', $asistencia->estado === 'confirmado'
            ? '¡Confirmado! Nos vemos en la cancha.'
            : 'El cupo está lleno: quedaste en lista de espera.');
    }

    public function noVoy(Request $request, PichangaFecha $fecha)
    {
        abort_unless($fecha->pichanga->esMiembro($request->user()), 403);

        if (! $fecha->estaAbierta()) {
            return back()->withErrors(['fecha' => 'Las inscripciones de esta fecha están cerradas. Avísale al organizador.']);
        }

        $fecha->bajar($request->user());

        return back()->with('status', 'Listo, quedaste fuera de esta fecha.');
    }

    // Arma equipos A/B al azar con los confirmados.
    public function sortear(Request $request, PichangaFecha $fecha)
    {
        abort_unless($fecha->pichanga->esOrganizador($request->user()), 403);

        $fecha->confirmados()->get()->shuffle()->values()
            ->each(fn ($asistencia, $i) => $asistencia->update(['equipo' => $i % 2 === 0 ? 'A' : 'B']));

        return back()->with('status', 'Equipos sorteados.');
    }

    // Planilla del organizador: equipo, pago y goles de cada confirmado + marcador.
    public function planilla(Request $request, PichangaFecha $fecha)
    {
        abort_unless($fecha->pichanga->esOrganizador($request->user()), 403);

        $data = $request->validate([
            'jugadores' => ['array'],
            'jugadores.*.equipo' => ['nullable', Rule::in(['A', 'B'])],
            'jugadores.*.goles' => ['nullable', 'integer', 'min:0', 'max:50'],
            'goles_a' => ['nullable', 'integer', 'min:0', 'max:99'],
            'goles_b' => ['nullable', 'integer', 'min:0', 'max:99'],
        ]);

        foreach ($fecha->confirmados()->get() as $asistencia) {
            $fila = $data['jugadores'][$asistencia->id] ?? [];

            $asistencia->update([
                'equipo' => $fila['equipo'] ?? null,
                'goles' => $fila['goles'] ?? 0,
                'pagado' => $request->boolean("jugadores.{$asistencia->id}.pagado"),
            ]);
        }

        $fecha->update([
            'goles_a' => $data['goles_a'] ?? null,
            'goles_b' => $data['goles_b'] ?? null,
            'estado' => $request->boolean('jugada') ? 'jugada' : $fecha->estado,
        ]);

        return back()->with('status', 'Planilla guardada.');
    }

    // Abrir / cerrar inscripciones, cancelar o reabrir la fecha.
    public function estado(Request $request, PichangaFecha $fecha)
    {
        abort_unless($fecha->pichanga->esOrganizador($request->user()), 403);

        $data = $request->validate([
            'estado' => ['required', Rule::in(['abierta', 'cerrada', 'cancelada'])],
        ]);

        $fecha->update($data);

        $mensajes = [
            'abierta' => 'Inscripciones abiertas.',
            'cerrada' => 'Inscripciones cerradas.',
            'cancelada' => 'Fecha cancelada.',
        ];

        return back()->with('status', $mensajes[$data['estado']]);
    }
}
