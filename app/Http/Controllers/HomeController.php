<?php

namespace App\Http\Controllers;

use App\Models\Liga;

class HomeController extends Controller
{
    // Portada pública: qué es la app, accesos para crear pichanga o campeonato
    // y el listado de campeonatos públicos.
    public function index()
    {
        $campeonatos = Liga::where('activa', true)
            ->where('publica', true)
            ->withCount(['torneos', 'equipos'])
            ->orderBy('nombre')
            ->get();

        return view('home', compact('campeonatos'));
    }
}
