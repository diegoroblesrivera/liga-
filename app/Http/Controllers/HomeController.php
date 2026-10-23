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

        return view('home', [
            'campeonatos' => $campeonatos,
            'precio' => \App\Models\Ajuste::precioProAnual(),
            'limite' => Liga::EQUIPOS_PLAN_GRATIS,
        ]);
    }

    // Planes: Gratis vs Pro, con el precio vigente y los logros que solo da el Pro.
    public function precios()
    {
        return view('precios', [
            'precio' => \App\Models\Ajuste::precioProAnual(),
            'limite' => Liga::EQUIPOS_PLAN_GRATIS,
            'logrosPro' => collect(\App\Support\Logros\Catalogo::LOGROS)->filter(fn ($l) => $l['pro']),
        ]);
    }
}
