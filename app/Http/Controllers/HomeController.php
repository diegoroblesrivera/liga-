<?php

namespace App\Http\Controllers;

use App\Models\Partido;
use App\Models\Torneo;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    // Home público: posiciones, jornadas con swipe y goleadores. No requiere login.
    public function index(Request $request)
    {
        $torneos = Torneo::orderByDesc('id')->get();

        $torneo = $request->filled('torneo')
            ? $torneos->firstWhere('id', (int) $request->query('torneo'))
            : $torneos->first();

        $posiciones = collect();
        $goleadores = collect();
        $jornadas = collect();
        $indiceInicial = 0;

        if ($torneo) {
            $posiciones = DB::table('vista_posiciones')
                ->where('torneo_id', $torneo->id)
                ->orderByDesc('pts')->orderByDesc('dif')->orderByDesc('gf')
                ->get();

            $goleadores = DB::table('vista_goleadores')
                ->where('torneo_id', $torneo->id)
                ->orderByDesc('goles')
                ->limit(10)
                ->get();

            // Todas las jornadas con sus partidos, en orden.
            $jornadas = Partido::with(['equipoLocal', 'equipoVisita', 'cancha'])
                ->where('torneo_id', $torneo->id)
                ->orderBy('numero_fecha')
                ->orderBy('fecha_hora')
                ->get()
                ->groupBy('numero_fecha')
                ->map(fn ($partidos, $numero) => ['numero' => $numero, 'partidos' => $partidos])
                ->values();

            if ($jornadas->isNotEmpty()) {
                $indiceInicial = $this->jornadaPorDefecto($torneo, $jornadas);
            }
        }

        return view('home', compact('torneos', 'torneo', 'posiciones', 'goleadores', 'jornadas', 'indiceInicial'));
    }

    /**
     * Índice de la jornada a mostrar por defecto, según el CALENDARIO real:
     *  1) La jornada del próximo partido con fecha, de hoy en adelante.
     *  2) Si no hay partidos futuros con fecha, la del último partido jugado.
     *  3) Si ningún partido tiene fecha asignada, la primera jornada.
     * Las jornadas sin fecha no compiten por ser la predeterminada.
     */
    protected function jornadaPorDefecto(Torneo $torneo, $jornadas): int
    {
        $ahora = Carbon::now();

        // Próximo partido con fecha, de hoy en adelante.
        $proximo = Partido::where('torneo_id', $torneo->id)
            ->whereNotNull('fecha_hora')
            ->where('fecha_hora', '>=', $ahora)
            ->orderBy('fecha_hora')
            ->first();

        // Si no hay futuros, el último con fecha ya pasada (lo más reciente jugado).
        $referencia = $proximo ?? Partido::where('torneo_id', $torneo->id)
            ->whereNotNull('fecha_hora')
            ->where('fecha_hora', '<', $ahora)
            ->orderByDesc('fecha_hora')
            ->first();

        if (! $referencia) {
            return 0; // Ningún partido tiene fecha: primera jornada.
        }

        $pos = $jornadas->search(fn ($j) => (int) $j['numero'] === (int) $referencia->numero_fecha);

        return $pos === false ? 0 : $pos;
    }
}
