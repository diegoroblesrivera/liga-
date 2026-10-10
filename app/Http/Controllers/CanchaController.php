<?php

namespace App\Http\Controllers;

use App\Models\Cancha;
use Illuminate\Http\Request;

class CanchaController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $canchas = Cancha::withCount('partidos')->orderBy('nombre')->get();

        return view('canchas.index', compact('canchas'));
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        return view('canchas.create');
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        Cancha::create($this->validar($request));

        return redirect()->route('canchas.index')->with('status', 'Cancha creada.');
    }

    public function edit(Request $request, Cancha $cancha)
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        return view('canchas.edit', compact('cancha'));
    }

    public function update(Request $request, Cancha $cancha)
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $cancha->update($this->validar($request));

        return redirect()->route('canchas.index')->with('status', 'Cancha actualizada.');
    }

    public function destroy(Request $request, Cancha $cancha)
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        // Seguro: partidos.cancha_id queda en null si se borra la cancha (ver migración,
        // nullOnDelete), no se pierde ningún partido.
        $cancha->delete();

        return back()->with('status', 'Cancha eliminada.');
    }

    protected function validar(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'direccion' => ['nullable', 'string', 'max:200'],
        ]);
    }
}
