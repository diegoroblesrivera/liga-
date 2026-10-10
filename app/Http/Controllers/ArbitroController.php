<?php

namespace App\Http\Controllers;

use App\Models\Invitacion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ArbitroController extends Controller
{
    // Listado de árbitros + botón para generar el link (solo admin).
    public function index(Request $request)
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $arbitros = User::whereHas('roles', fn ($q) => $q->where('nombre', 'arbitro'))
            ->orderBy('name')
            ->get();

        return view('arbitros.index', compact('arbitros'));
    }

    // Genera una invitación de árbitro (un solo uso, sin equipo).
    public function invitar(Request $request)
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $invitacion = Invitacion::create([
            'equipo_id' => null,        // el árbitro no pertenece a un equipo
            'tipo' => 'arbitro',
            'token' => Str::random(40),
            'reutilizable' => false,    // un solo uso, como el DT
            'usos' => 0,
            'creada_por' => $request->user()->id,
        ]);

        return back()->with('link_invitacion', route('invitacion.show', $invitacion->token));
    }
}
