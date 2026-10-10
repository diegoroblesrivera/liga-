<?php

namespace App\Http\Controllers;

use App\Exports\PagosExport;
use App\Models\PagoPartido;
use App\Models\Partido;
use App\Models\Torneo;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class PagoPartidoController extends Controller
{
    public function edit(Partido $partido)
    {
        Gate::authorize('gestionar-pagos');

        $partido->load('equipoLocal', 'equipoVisita', 'pagos');

        return view('pagos.asignar', compact('partido'));
    }

    public function update(Request $request, Partido $partido)
    {
        Gate::authorize('gestionar-pagos');

        $data = $request->validate([
            'monto_pago' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        ]);

        $partido->update($data);

        return redirect()
            ->route('partidos.pago.edit', $partido)
            ->with('status', 'Monto de pago asignado correctamente.');
    }

    /**
     * Panel del admin: partidos con monto asignado del torneo elegido,
     * agrupados por jornada y paginados de a 5 jornadas por página.
     */
    public function panel(Request $request)
    {
        Gate::authorize('gestionar-pagos');

        $torneos = Torneo::orderByDesc('id')->get();
        $torneo = $this->resolverTorneo($request, $torneos);

        $jornadas = collect();
        $paginaJornadas = null;

        if ($torneo) {
            // Paginamos la lista de jornadas (numero_fecha) que tienen pagos.
            $paginaJornadas = Partido::where('torneo_id', $torneo->id)
                ->whereNotNull('monto_pago')
                ->select('numero_fecha')
                ->distinct()
                ->orderBy('numero_fecha')
                ->paginate(5)
                ->appends(['torneo' => $torneo->id]);

            $nums = collect($paginaJornadas->items())->pluck('numero_fecha')->all();

            if (! empty($nums)) {
                $jornadas = Partido::where('torneo_id', $torneo->id)
                    ->whereNotNull('monto_pago')
                    ->whereIn('numero_fecha', $nums)
                    ->with(['equipoLocal', 'equipoVisita', 'pagos'])
                    ->orderBy('numero_fecha')
                    ->orderBy('fecha_hora')
                    ->get()
                    ->groupBy('numero_fecha');
            }
        }

        return view('pagos.panel', compact('torneos', 'torneo', 'jornadas', 'paginaJornadas'));
    }

    /**
     * Exporta a Excel todos los pagos del torneo seleccionado.
     */
    public function exportarExcel(Request $request)
    {
        Gate::authorize('gestionar-pagos');

        $torneo = $this->resolverTorneo($request, Torneo::orderByDesc('id')->get());
        abort_unless($torneo, 404);

        $nombre = 'pagos_'.Str::slug($torneo->nombre).'.xlsx';

        return Excel::download(new PagosExport($this->filasDe($torneo)), $nombre);
    }

    /**
     * Exporta a PDF todos los pagos del torneo seleccionado.
     */
    public function exportarPdf(Request $request)
    {
        Gate::authorize('gestionar-pagos');

        $torneo = $this->resolverTorneo($request, Torneo::orderByDesc('id')->get());
        abort_unless($torneo, 404);

        $filas = $this->filasDe($torneo);

        $pdf = Pdf::loadView('pagos.pdf', compact('torneo', 'filas'));

        return $pdf->download('pagos_'.Str::slug($torneo->nombre).'.pdf');
    }

    public function misPagos(Request $request)
    {
        $equipoId = $request->user()->equipo_id;

        abort_unless($equipoId, 403, 'Tu usuario no tiene un equipo asociado.');

        $partidos = Partido::query()
            ->whereNotNull('monto_pago')
            ->where(function ($q) use ($equipoId) {
                $q->where('equipo_local_id', $equipoId)
                    ->orWhere('equipo_visita_id', $equipoId);
            })
            ->with(['equipoLocal', 'equipoVisita', 'pagos' => function ($q) use ($equipoId) {
                $q->where('equipo_id', $equipoId);
            }])
            ->orderBy('numero_fecha')
            ->orderByDesc('fecha_hora')
            ->paginate(20);

        return view('pagos.mis-pagos', compact('partidos'));
    }

    public function create(Partido $partido)
    {
        $this->autorizarDt($partido);

        abort_unless($partido->monto_pago !== null, 404, 'Este partido no tiene un pago asignado.');

        $equipoId = Auth::user()->equipo_id;
        $pago = PagoPartido::where('partido_id', $partido->id)
            ->where('equipo_id', $equipoId)
            ->first();

        return view('pagos.subir-comprobante', compact('partido', 'pago'));
    }

    /**
     * Guarda / reemplaza el comprobante en public_html/uploads/comprobantes/{partido}.
     */
    public function store(Request $request, Partido $partido)
    {
        $this->autorizarDt($partido);

        abort_unless($partido->monto_pago !== null, 404, 'Este partido no tiene un pago asignado.');

        $request->validate([
            'comprobante' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        $equipoId = Auth::user()->equipo_id;

        $pago = PagoPartido::firstOrNew([
            'partido_id' => $partido->id,
            'equipo_id' => $equipoId,
        ]);

        $carpeta = 'uploads/comprobantes/'.$partido->id;
        $dir = public_path($carpeta);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if ($pago->exists && $pago->comprobante_path && is_file(public_path($pago->comprobante_path))) {
            @unlink(public_path($pago->comprobante_path));
        }

        $file = $request->file('comprobante');
        $nombre = 'comp_'.$partido->id.'_'.$equipoId.'_'.time().'.'.$file->getClientOriginalExtension();
        $file->move($dir, $nombre);

        $pago->fill([
            'comprobante_path' => $carpeta.'/'.$nombre,
            'subido_por' => Auth::id(),
            'fecha_subida' => now(),
        ])->save();

        return redirect()
            ->route('pagos.mis-pagos')
            ->with('status', 'Comprobante subido correctamente.');
    }

    // --- Helpers ---

    private function resolverTorneo(Request $request, $torneos): ?Torneo
    {
        return $request->filled('torneo')
            ? $torneos->firstWhere('id', (int) $request->query('torneo'))
            : $torneos->first();
    }

    /**
     * Arma las filas de exportación (una por equipo y partido) del torneo.
     */
    private function filasDe(Torneo $torneo): array
    {
        $partidos = Partido::where('torneo_id', $torneo->id)
            ->whereNotNull('monto_pago')
            ->with(['equipoLocal', 'equipoVisita', 'pagos'])
            ->orderBy('numero_fecha')
            ->orderBy('fecha_hora')
            ->get();

        $filas = [];

        foreach ($partidos as $p) {
            $nombrePartido = $p->equipoLocal->nombre.' vs '.$p->equipoVisita->nombre;
            $fechaHora = $p->fecha_hora?->format('d/m/Y H:i') ?? 'Sin fecha';

            $equipos = [
                [$p->equipo_local_id, $p->equipoLocal->nombre],
                [$p->equipo_visita_id, $p->equipoVisita->nombre],
            ];

            foreach ($equipos as [$eqId, $eqNombre]) {
                $pago = $p->pagos->firstWhere('equipo_id', $eqId);
                $pagado = $pago && $pago->comprobante_path;

                $filas[] = [
                    'jornada' => $p->numero_fecha,
                    'fecha' => $fechaHora,
                    'partido' => $nombrePartido,
                    'equipo' => $eqNombre,
                    'monto' => (float) $p->monto_pago,
                    'estado' => $pagado ? 'Pagado' : 'Pendiente',
                    'subida' => ($pagado && $pago->fecha_subida)
                        ? Carbon::parse($pago->fecha_subida)->format('d/m/Y H:i')
                        : '',
                ];
            }
        }

        return $filas;
    }

    private function autorizarDt(Partido $partido): void
    {
        if (Gate::allows('gestionar-pagos')) {
            return;
        }

        $user = Auth::user();

        $perteneceAlPartido = $user->equipo_id && in_array($user->equipo_id, [
            $partido->equipo_local_id,
            $partido->equipo_visita_id,
        ]);

        abort_unless($perteneceAlPartido, 403, 'No tienes acceso a los pagos de este partido.');
    }
}
