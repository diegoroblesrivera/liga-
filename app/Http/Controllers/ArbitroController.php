<?php

namespace App\Http\Controllers;

use App\Models\Invitacion;
use App\Support\LigaActual;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ArbitroController extends Controller
{
    // Árbitros del campeonato + botón para generar el link (solo admin).
    public function index(Request $request)
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $arbitros = LigaActual::liga()->arbitros()->orderBy('name')->get();

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

        $link = route('invitacion.show', $invitacion->token);
        $mensaje = 'Te invito a ser árbitro del campeonato *'.\App\Support\LigaActual::liga()?->nombre.'* en '.config('app.name').". Regístrate aquí: {$link}";

        return back()->with('link_invitacion', $link)->with('mensaje_invitacion', $mensaje);
    }
}
