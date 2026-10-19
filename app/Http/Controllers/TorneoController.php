<?php

namespace App\Http\Controllers;

use App\Models\Torneo;
use Illuminate\Http\Request;

class TorneoController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $torneos = Torneo::withCount(['partidos', 'equipos'])
            ->orderByDesc('fecha_inicio')
            ->get();

        return view('torneos.index', compact('torneos'));
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        return view('torneos.create');
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        Torneo::create($this->validar($request));

        return redirect()->route('torneos.index')->with('status', 'Torneo creado.');
    }

    public function edit(Request $request, Torneo $torneo)
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        return view('torneos.edit', compact('torneo'));
    }

    public function update(Request $request, Torneo $torneo)
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $torneo->update($this->validar($request));

        return redirect()->route('torneos.index')->with('status', 'Torneo actualizado.');
    }

    // Cerrar el torneo: se entregan los logros de Campeón, Pichichi y Asistencia perfecta.
    public function cerrar(Request $request, Torneo $torneo)
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $pendientes = $torneo->partidos()->whereIn('estado', ['programado', 'en_juego'])->count();
        if ($pendientes) {
            return back()->withErrors(['torneo' => "Faltan {$pendientes} partidos por jugar (o suspenderlos) antes de cerrar el torneo."]);
        }
        if (! $torneo->partidos()->where('estado', 'finalizado')->exists()) {
            return back()->withErrors(['torneo' => 'El torneo no tiene partidos jugados.']);
        }

        $torneo->forceFill(['cerrado_en' => now()])->save();

        return back()->with('status', 'Torneo cerrado: se entregaron los logros de campeón y goleador.');
    }

    public function reabrir(Request $request, Torneo $torneo)
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $torneo->forceFill(['cerrado_en' => null])->save();

        return back()->with('status', 'Torneo reabierto: se quitaron los logros de cierre hasta que lo vuelvas a cerrar.');
    }

    public function destroy(Request $request, Torneo $torneo)
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        // OJO: torneo_id en partidos tiene cascadeOnDelete (ver migración) -> borrar
        // un torneo borraría TODOS sus partidos de un tirón. Por seguridad, se bloquea
        // acá si el torneo ya tiene partidos cargados.
        if ($torneo->partidos()->exists()) {
            return back()->withErrors([
                'torneo' => 'Este torneo ya tiene partidos cargados. No se puede eliminar porque se borrarían todos junto con él.',
            ]);
        }

        $torneo->delete();

        return back()->with('status', 'Torneo eliminado.');
    }

    protected function validar(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'temporada' => ['nullable', 'string', 'max:20'],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
        ]);
    }
}
