<?php

namespace App\Http\Controllers;

use App\Models\Torneo;
use App\Support\Torneos\Formato;
use App\Support\Torneos\Vista;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TablaController extends Controller
{
    // Posiciones de un torneo según su formato (tabla, grupos y/o llave).
    public function posiciones(Request $request)
    {
        [$torneos, $torneo] = $this->torneoSeleccionado($request);

        // Según el formato: tabla general, tablas por grupo y/o la llave.
        $secciones = $torneo ? Vista::secciones($torneo) : [];
        $segunda = $torneo ? Formato::segundaPendiente($torneo) : null;

        return view('tablas.posiciones', compact('torneos', 'torneo', 'secciones', 'segunda'));
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
