<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ConfiguracionController extends Controller
{
    // Panel general de administración: hoy son accesos directos a pantallas
    // que ya existen (Jugadores, Equipos, Árbitros, Fixture) más un par de
    // secciones marcadas como "próximamente" (Torneos, Canchas) para cuando
    // se arme su ABM. Ir agregando tarjetas acá a medida que se sumen secciones.
    public function index(Request $request)
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        return view('configuracion.index');
    }
}
