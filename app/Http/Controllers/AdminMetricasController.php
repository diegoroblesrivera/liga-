<?php

namespace App\Http\Controllers;

use App\Models\Liga;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

// Métricas del producto para el super admin (Panel TuTorneo). Consultas sin scope de liga.
class AdminMetricasController extends Controller
{
    private const SEMANAS = 12;

    public function index()
    {
        $hace = fn (int $dias) => now()->subDays($dias);
        $activos = fn (int $dias) => DB::table('actividad_diaria')->where('fecha', '>', today()->subDays($dias))->distinct()->count('user_id');
        $jugadoDesde = fn ($q, Carbon $desde) => $q->where('partidos.estado', 'finalizado')
            ->whereRaw('COALESCE(partidos.finalizado_en, partidos.fecha_hora, partidos.updated_at) >= ?', [$desde]);

        $usuarios = [
            'total' => DB::table('users')->count(),
            'nuevos_7' => DB::table('users')->where('created_at', '>=', $hace(7))->count(),
            'nuevos_30' => DB::table('users')->where('created_at', '>=', $hace(30))->count(),
            'activos_1' => $activos(1),
            'activos_7' => $activos(7),
            'activos_30' => $activos(30),
        ];

        // Campeonatos con partidos jugados en los últimos 30 días.
        $actividadLigas = DB::table('partidos')
            ->join('torneos', 'torneos.id', '=', 'partidos.torneo_id')
            ->tap(fn ($q) => $jugadoDesde($q, $hace(30)))
            ->groupBy('torneos.liga_id')
            ->selectRaw('torneos.liga_id, COUNT(partidos.id) AS partidos')
            ->pluck('partidos', 'liga_id');

        $ligas = Liga::with('owner')->withCount('equipos')->get();

        $campeonatos = [
            'total' => $ligas->count(),
            'activos_30' => $actividadLigas->count(),
            'pro' => $ligas->filter->esPro()->count(),
            'nuevos_30' => $ligas->where('created_at', '>=', $hace(30))->count(),
        ];

        // Oportunidades: gratis cerca o en el límite de equipos.
        $cercaDelLimite = $ligas->reject->esPro()
            ->filter(fn ($l) => $l->equipos_count >= Liga::EQUIPOS_PLAN_GRATIS - 2)
            ->sortByDesc('equipos_count')->values();

        $masActivos = $ligas->map(fn ($l) => ['liga' => $l, 'partidos' => (int) ($actividadLigas[$l->id] ?? 0)])
            ->filter(fn ($f) => $f['partidos'] > 0)->sortByDesc('partidos')->take(10)->values();

        $actividad = [
            'partidos' => DB::table('partidos')->tap(fn ($q) => $jugadoDesde($q, $hace(30)))->count(),
            'goles' => DB::table('goles')->where('created_at', '>=', $hace(30))->count(),
            'pichangas' => DB::table('pichanga_fechas')->where('estado', 'jugada')->where('fecha', '>=', $hace(30)->toDateString())->count(),
            'asistencias' => DB::table('asistencias')->where('created_at', '>=', $hace(30))->count(),
            'logros' => DB::table('logros')->where('obtenido_en', '>=', $hace(30))->count(),
        ];

        $pagos = DB::table('pagos_pro')->where('estado', 'aprobado')->where('medio', 'mercadopago');
        $ingresos = [
            'total' => (int) (clone $pagos)->sum('monto'),
            'mes' => (int) (clone $pagos)->where('pagado_en', '>=', now()->startOfMonth())->sum('monto'),
            'pagos_mes' => (clone $pagos)->where('pagado_en', '>=', now()->startOfMonth())->count(),
        ];
        $porVencer = $ligas->filter(fn ($l) => $l->esPro() && $l->pro_hasta->lte(now()->addDays(30)))->sortBy('pro_hasta')->values();

        return view('admin.metricas', compact(
            'usuarios', 'campeonatos', 'cercaDelLimite', 'masActivos', 'actividad', 'ingresos', 'porVencer'
        ) + ['semanas' => $this->semanas($jugadoDesde)]);
    }

    // Últimas semanas (lunes a domingo): usuarios nuevos, activos y partidos jugados.
    private function semanas(callable $jugadoDesde): array
    {
        $filas = [];
        for ($i = self::SEMANAS - 1; $i >= 0; $i--) {
            $desde = now()->startOfWeek()->subWeeks($i);
            $hasta = $desde->copy()->addWeek();

            $filas[] = [
                'etiqueta' => $desde->format('d/m'),
                'nuevos' => DB::table('users')->where('created_at', '>=', $desde)->where('created_at', '<', $hasta)->count(),
                'activos' => DB::table('actividad_diaria')->where('fecha', '>=', $desde->toDateString())->where('fecha', '<', $hasta->toDateString())->distinct()->count('user_id'),
                'partidos' => DB::table('partidos')->tap(fn ($q) => $jugadoDesde($q, $desde))
                    ->whereRaw('COALESCE(partidos.finalizado_en, partidos.fecha_hora, partidos.updated_at) < ?', [$hasta])->count(),
            ];
        }

        return $filas;
    }
}
