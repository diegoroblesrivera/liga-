<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\Invitacion;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InvitacionController extends Controller
{
    // Admin genera invitación de DT; DT genera invitación de jugador para su equipo.
    public function store(Request $request, Equipo $equipo)
    {
        $data = $request->validate([
            'tipo' => ['required', 'in:dt,jugador'],
            'email' => ['nullable', 'email', 'max:180'],
            'expira_en' => ['nullable', 'date', 'after:now'],
        ]);

        // La autorización depende del tipo (ver InvitacionPolicy@create).
        $this->authorize('create', [Invitacion::class, $equipo, $data['tipo']]);

        $invitacion = Invitacion::create([
            'equipo_id' => $equipo->id,
            'tipo' => $data['tipo'],
            'token' => Str::random(40),
            'email' => $data['email'] ?? null,
            'reutilizable' => $data['tipo'] === 'jugador', // dt = un uso; jugador = link compartido
            'usos' => 0,
            'expira_en' => $data['expira_en'] ?? null,
            'creada_por' => $request->user()->id,
        ]);

        $link = route('invitacion.show', $invitacion->token);
        $liga = \App\Support\LigaActual::liga()?->nombre;
        $mensaje = $data['tipo'] === 'dt'
            ? "¡Hola! Te invito a ser DT de *{$equipo->nombre}* en el campeonato *{$liga}* en ".config('app.name').". Inscríbete aquí: {$link}"
            : "⚽ ¡Súmate al plantel de *{$equipo->nombre}* ({$liga}) en ".config('app.name')."! Inscríbete aquí: {$link}";

        return back()->with('link_invitacion', $link)->with('mensaje_invitacion', $mensaje);
    }

    // Revocar una invitación (admin siempre; DT solo las de jugador de su equipo).
    public function destroy(Invitacion $invitacion)
    {
        $this->authorize('delete', $invitacion);

        $invitacion->delete();

        return back()->with('status', 'Invitación revocada.');
    }
}
