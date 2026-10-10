<?php

namespace App\Http\Controllers;

use App\Models\PichangaAsistencia;
use App\Models\PichangaFecha;
use App\Support\Run;
use Illuminate\Http\Request;

class PichangaInvitadoController extends Controller
{
    // Un jugador confirmado lleva a alguien sin cuenta. No ocupa cupo, pero juega y paga.
    public function store(Request $request, PichangaFecha $fecha)
    {
        $user = $request->user();

        $confirmado = $fecha->asistencias()
            ->where('user_id', $user->id)
            ->where('estado', 'confirmado')
            ->exists();

        if (! $confirmado) {
            return back()->withErrors(['invitado' => 'Para llevar un invitado primero tienes que estar confirmado.']);
        }

        if (! $fecha->estaAbierta()) {
            return back()->withErrors(['invitado' => 'Las inscripciones de esta fecha están cerradas.']);
        }

        if ($fecha->invitadosDe($user)->count() >= PichangaFecha::MAX_INVITADOS_POR_JUGADOR) {
            return back()->withErrors(['invitado' => 'Puedes llevar como máximo '.PichangaFecha::MAX_INVITADOS_POR_JUGADOR.' invitados por fecha.']);
        }

        $data = $request->validate([
            'invitado_nombre' => ['nullable', 'string', 'max:120'],
            'invitado_run' => ['nullable', 'string', 'max:15', function (string $attribute, mixed $value, \Closure $fail) {
                if (Run::normalizar($value) === null) {
                    $fail('El RUN del invitado no es válido. Revisa el número y el dígito verificador.');
                }
            }],
        ]);

        $fecha->agregarInvitado(
            $user,
            isset($data['invitado_nombre']) ? trim($data['invitado_nombre']) : null,
            Run::normalizar($data['invitado_run'] ?? null)
        );

        return back()->with('status', 'Invitado agregado. Juega contigo y entra en la división del valor de la cancha.');
    }

    // Quitar un invitado: quien lo trajo o el organizador.
    public function destroy(Request $request, PichangaAsistencia $asistencia)
    {
        abort_unless($asistencia->esInvitado(), 404);

        $user = $request->user();
        $esOrganizador = $asistencia->fecha->pichanga->esOrganizador($user);

        abort_unless($asistencia->invitado_por === $user->id || $esOrganizador, 403);

        $asistencia->delete();

        return back()->with('status', 'Invitado quitado.');
    }
}
