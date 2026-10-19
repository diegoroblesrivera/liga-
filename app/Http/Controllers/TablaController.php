<?php

namespace App\Http\Controllers;

use App\Models\Torneo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TablaController extends Controller
{
    // Tabla de posiciones de un torneo (lee la vista SQL vista_posiciones).
    public function posiciones(Request $request)
    {
        [$torneos, $torneo] = $this->torneoSeleccionado($request);

        $posiciones = $torneo
            ? DB::table('vista_posiciones')
                ->where('torneo_id', $torneo->id)
                ->orderByDesc('pts')
                ->orderByDesc('dif')
                ->orderByDesc('gf')
                ->get()
            : collect();

        return view('tablas.posiciones', compact('torneos', 'torneo', 'posiciones'));
    }

    // Tabla de goleadores de un torneo (lee la vista SQL vista_goleadores).
    public function goleadores(Request $request)
    {
        [$torneos, $torneo] = $this->torneoSeleccionado($request);

        $goleadores = $torneo
            ? DB::table('vista_goleadores')
                ->where('torneo_id', $torneo->id)
                ->orderByDesc('goles')
                ->get()
            : collect();

        $insignias = \App\Support\Logros\Insignias::mejores($goleadores->pluck('jugador_id'));

        return view('tablas.goleadores', compact('torneos', 'torneo', 'goleadores', 'insignias'));
    }

    // Resuelve el torneo elegido (?torneo=ID) o cae en el primero disponible.
    protected function torneoSeleccionado(Request $request): array
    {
        $torneos = Torneo::orderByDesc('id')->get();

        $torneo = $request->filled('torneo')
            ? $torneos->firstWhere('id', (int) $request->query('torneo'))
            : $torneos->first();

        return [$torneos, $torneo];
    }
}
