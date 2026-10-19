<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Jugador;
use App\Models\Liga;
use App\Models\ListaFecha;
use App\Models\Partido;
use App\Models\Torneo;
use App\Support\LigaActual;
use App\Support\Lista;
use Illuminate\Http\Request;

// Lista de asistencia por QR (opcional por campeonato).
class ListaController extends Controller
{
    // QR de la fecha y lista en vivo: organizador, árbitro o DT de la fecha.
    public function fecha(Request $request, Torneo $torneo, int $numero)
    {
        [$partidos, $lista] = $this->fechaAutorizada($request, $torneo, $numero);

        return view('lista.fecha', [
            'torneo' => $torneo,
            'numero' => $numero,
            'partidos' => $partidos,
            'lista' => $lista,
            'qr' => Lista::qrSvg($lista->url()),
            'liga' => LigaActual::liga(),
        ]);
    }

    // Hoja para imprimir y dejar en la mesa de la organización.
    public function imprimir(Request $request, Torneo $torneo, int $numero)
    {
        [$partidos, $lista] = $this->fechaAutorizada($request, $torneo, $numero);

        return view('lista.imprimir', [
            'torneo' => $torneo,
            'numero' => $numero,
            'partidos' => $partidos,
            'qr' => Lista::qrSvg($lista->url(), 520),
            'liga' => LigaActual::liga(),
        ]);
    }

    // Nuevo QR (el impreso deja de servir). Solo el organizador.
    public function regenerar(Request $request, Torneo $torneo, int $numero)
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        ListaFecha::deFecha($torneo, $numero)->update(['token' => ListaFecha::nuevoToken()]);

        return back()->with('status', 'QR nuevo generado: el anterior ya no sirve.');
    }

    // El jugador escanea el QR: queda presente en el partido de su equipo en esa fecha.
    public function escanear(Request $request, string $token)
    {
        $lista = ListaFecha::withoutGlobalScopes()->where('token', $token)->firstOrFail();
        $torneo = Torneo::withoutGlobalScopes()->findOrFail($lista->torneo_id);
        $liga = Liga::findOrFail($torneo->liga_id);

        $resultado = LigaActual::usar($liga, function () use ($request, $lista, $liga) {
            $base = ['liga' => $liga, 'numero' => $lista->numero_fecha];

            if (! $liga->pedir_lista) {
                return $base + ['estado' => 'desactivada'];
            }

            $partidos = Partido::where('torneo_id', $lista->torneo_id)
                ->where('numero_fecha', $lista->numero_fecha)
                ->with('equipoLocal', 'equipoVisita')
                ->get();

            $equipos = $partidos->flatMap(fn ($p) => [$p->equipo_local_id, $p->equipo_visita_id]);
            $jugador = Jugador::where('user_id', $request->user()->id)->whereIn('equipo_id', $equipos)->first();

            if (! $jugador) {
                return $base + ['estado' => 'no_inscrito'];
            }

            $partido = $partidos->first(fn ($p) => in_array($jugador->equipo_id, [$p->equipo_local_id, $p->equipo_visita_id], true));
            $base += ['jugador' => $jugador, 'partido' => $partido];

            if (! Lista::abierta($partido)) {
                return $base + ['estado' => $partido->fecha_hora && $partido->fecha_hora->isFuture() ? 'otro_dia' : 'cerrada'];
            }

            $asistencia = Asistencia::firstOrCreate(
                ['partido_id' => $partido->id, 'jugador_id' => $jugador->id],
                ['metodo' => 'qr', 'marcado_por' => $request->user()->id]
            );

            return $base + ['estado' => $asistencia->wasRecentlyCreated ? 'ok' : 'ya'];
        });

        return view('lista.escaneo', $resultado);
    }

    // Marcar presente a mano (jugadores sin celular).
    public function marcar(Request $request, Partido $partido)
    {
        $data = $request->validate(['jugador_id' => ['required', 'integer']]);
        $jugador = $this->jugadorDelPartido($partido, (int) $data['jugador_id']);

        abort_unless(Lista::puedeMarcar($request->user(), $partido, $jugador), 403);

        Asistencia::firstOrCreate(
            ['partido_id' => $partido->id, 'jugador_id' => $jugador->id],
            ['metodo' => 'manual', 'marcado_por' => $request->user()->id]
        );

        return back()->with('status', "{$jugador->nombre} quedó presente.");
    }

    public function desmarcar(Request $request, Partido $partido, Jugador $jugador)
    {
        $jugador = $this->jugadorDelPartido($partido, $jugador->id);

        abort_unless(Lista::puedeMarcar($request->user(), $partido, $jugador), 403);

        Asistencia::where('partido_id', $partido->id)->where('jugador_id', $jugador->id)->delete();

        return back()->with('status', "{$jugador->nombre} salió de la lista.");
    }

    // Activar / desactivar la lista y si es obligatoria (organizador).
    public function configurar(Request $request)
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $pedir = $request->boolean('pedir_lista');

        LigaActual::liga()->update([
            'pedir_lista' => $pedir,
            'lista_obligatoria' => $pedir && $request->boolean('lista_obligatoria'),
        ]);

        return back()->with('status', $pedir ? 'Lista de asistencia activada.' : 'Lista de asistencia desactivada.');
    }

    private function fechaAutorizada(Request $request, Torneo $torneo, int $numero): array
    {
        $partidos = Lista::partidosDeFecha($torneo, $numero);

        abort_if($partidos->isEmpty(), 404);
        abort_unless(Lista::puedeGestionarFecha($request->user(), $partidos), 403);

        return [$partidos, ListaFecha::deFecha($torneo, $numero)];
    }

    private function jugadorDelPartido(Partido $partido, int $jugadorId): Jugador
    {
        $jugador = Jugador::findOrFail($jugadorId);

        abort_unless(in_array($jugador->equipo_id, [$partido->equipo_local_id, $partido->equipo_visita_id], true), 422, 'El jugador no juega este partido.');

        return $jugador;
    }
}
