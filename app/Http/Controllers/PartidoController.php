<?php

namespace App\Http\Controllers;

use App\Models\Cancha;
use App\Models\Equipo;
use App\Models\Partido;
use App\Models\Torneo;
use App\Support\LigaActual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PartidoController extends Controller
{
    // Fixture completo, como carrusel de jornadas (en orden natural 1, 2, 3...).
    // El carrusel arranca posicionado en la jornada "actual": la próxima a
    // jugarse, o la última jugada si no quedan partidos futuros.
    public function index()
    {
        $this->authorize('viewAny', Partido::class);

        $jornadas = Partido::with(['torneo', 'equipoLocal', 'equipoVisita', 'cancha', 'arbitro'])
            ->orderBy('numero_fecha')
            ->orderBy('fecha_hora')
            ->get()
            ->groupBy('numero_fecha')
            ->map(fn ($partidosDeLaFecha, $numero) => [
                'numero' => $numero,
                'partidos' => $partidosDeLaFecha,
            ])
            ->values();

        $fechaActual = $this->jornadaActual();

        $indiceInicial = $jornadas->search(
            fn ($jornada) => $fechaActual !== null && (int) $jornada['numero'] === (int) $fechaActual
        );

        if ($indiceInicial === false) {
            $indiceInicial = 0;
        }

        return view('partidos.index', compact('jornadas', 'indiceInicial'));
    }

    public function create()
    {
        $this->authorize('create', Partido::class);

        return view('partidos.create', $this->datosFormulario());
    }

    public function store(Request $request)
    {
        $this->authorize('create', Partido::class);

        $partido = Partido::create($this->validar($request));

        return redirect()->route('partidos.show', $partido)->with('status', 'Partido programado.');
    }

    public function show(Partido $partido)
    {
        $this->authorize('view', $partido);

        $partido->load([
            'torneo', 'cancha', 'arbitro',
            'equipoLocal.jugadores', 'equipoVisita.jugadores',
            'goles.jugador',
            'pagos',
        ]);

        $arbitros = LigaActual::liga()->arbitros()->orderBy('name')->get();

        $canchas = Cancha::orderBy('nombre')->get();

        return view('partidos.show', compact('partido', 'arbitros', 'canchas'));
    }

    public function edit(Partido $partido)
    {
        $this->authorize('update', $partido);

        return view('partidos.edit', array_merge(['partido' => $partido], $this->datosFormulario()));
    }

    public function update(Request $request, Partido $partido)
    {
        $this->authorize('update', $partido);

        $partido->update($this->validar($request));

        return redirect()->route('partidos.show', $partido)->with('status', 'Partido actualizado.');
    }

    // Edición rápida de fecha, hora y cancha desde la ficha del partido (solo admin).
    public function actualizarProgramacion(Request $request, Partido $partido)
    {
        $this->authorize('update', $partido);

        $data = $request->validate([
            'fecha_hora' => ['nullable', 'date'],
            'cancha_id' => ['nullable', LigaActual::existe('canchas')],
        ]);

        $partido->update([
            'fecha_hora' => $data['fecha_hora'] ?? null,
            'cancha_id' => $data['cancha_id'] ?? null,
        ]);

        return back()->with('status', 'Fecha y cancha actualizadas.');
    }

    // Asignar (o cambiar) el árbitro de un partido. Solo admin (Policy update).
    public function asignarArbitro(Request $request, Partido $partido)
    {
        $this->authorize('update', $partido);

        $data = $request->validate([
            'arbitro_id' => ['nullable', LigaActual::existeArbitro()],
        ]);

        $partido->update(['arbitro_id' => $data['arbitro_id'] ?? null]);

        return back()->with('status', 'Árbitro actualizado.');
    }

    // Eliminar un partido (solo admin). Goles, tarjetas, convocatorias y pagos se borran
    // en cascada en la base; las imágenes de los comprobantes se borran aquí.
    public function destroy(Partido $partido)
    {
        $this->authorize('delete', $partido);

        $nombre = $partido->equipoLocal->nombre.' vs '.$partido->equipoVisita->nombre;

        DB::transaction(fn () => $partido->delete());
        $this->borrarComprobantes([$partido->id]);

        return redirect()->route('partidos.index')->with('status', "Partido eliminado: {$nombre}.");
    }

    // Eliminar una fecha (jornada) completa de un torneo (solo admin).
    public function destroyFecha(Request $request, Torneo $torneo, int $numero)
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $partidos = $torneo->partidos()->where('numero_fecha', $numero)->get();
        abort_if($partidos->isEmpty(), 404);

        DB::transaction(fn () => $torneo->partidos()->where('numero_fecha', $numero)->delete());
        $this->borrarComprobantes($partidos->pluck('id')->all());

        return redirect()->route('partidos.index')
            ->with('status', "Fecha {$numero} de \"{$torneo->nombre}\" eliminada ({$partidos->count()} ".($partidos->count() === 1 ? 'partido' : 'partidos').').');
    }

    // --- Acciones del árbitro (ciclo de vida) ---

    public function iniciar(Partido $partido)
    {
        $this->authorize('iniciar', $partido);

        $partido->update(['estado' => 'en_juego', 'iniciado_en' => now()]);

        return back()->with('status', 'Partido iniciado. Ya puedes cargar goles.');
    }

    public function finalizar(Partido $partido)
    {
        $this->authorize('finalizar', $partido);

        $local = $partido->goles->where('equipo_id', $partido->equipo_local_id)->count();
        $visita = $partido->goles->where('equipo_id', $partido->equipo_visita_id)->count();

        $partido->update([
            'estado' => 'finalizado',
            'finalizado_en' => now(),
            'goles_local' => $local,
            'goles_visita' => $visita,
        ]);

        return redirect()->route('partidos.show', $partido)
            ->with('status', 'Partido finalizado. El resultado quedó registrado.');
    }

    // --- Helpers ---

    // Los comprobantes de pago de cada partido viven en storage/app/private/comprobantes-partidos/{partido}.
    protected function borrarComprobantes(array $partidoIds): void
    {
        foreach ($partidoIds as $id) {
            Storage::disk('local')->deleteDirectory('comprobantes-partidos/'.(int) $id);
        }
    }

    // Misma lógica que en el home: el próximo partido a jugarse (fecha_hora >= ahora),
    // y si no queda ninguno, la fecha del último partido jugado. Ignora partidos sin fecha.
    protected function jornadaActual(): ?int
    {
        $proximo = Partido::whereNotNull('fecha_hora')
            ->where('fecha_hora', '>=', now())
            ->orderBy('fecha_hora')
            ->first();

        if ($proximo) {
            return $proximo->numero_fecha;
        }

        $anterior = Partido::whereNotNull('fecha_hora')
            ->orderByDesc('fecha_hora')
            ->first();

        return $anterior?->numero_fecha;
    }

    protected function datosFormulario(): array
    {
        return [
            'torneos' => Torneo::orderBy('nombre')->get(),
            'equipos' => Equipo::orderBy('nombre')->get(),
            'canchas' => Cancha::orderBy('nombre')->get(),
            'arbitros' => LigaActual::liga()->arbitros()->orderBy('name')->get(),
        ];
    }

    protected function validar(Request $request): array
    {
        return $request->validate([
            'torneo_id' => ['required', LigaActual::existe('torneos')],
            'numero_fecha' => ['required', 'integer', 'min:1'],
            'equipo_local_id' => ['required', LigaActual::existe('equipos')],
            'equipo_visita_id' => ['required', 'different:equipo_local_id', LigaActual::existe('equipos')],
            'cancha_id' => ['nullable', LigaActual::existe('canchas')],
            'arbitro_id' => ['nullable', LigaActual::existeArbitro()],
            'fecha_hora' => ['nullable', 'date'],
        ], [
            'equipo_visita_id.different' => 'El local y la visita no pueden ser el mismo equipo.',
        ]);
    }
}
