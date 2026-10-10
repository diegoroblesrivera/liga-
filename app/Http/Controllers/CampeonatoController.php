<?php

namespace App\Http\Controllers;

use App\Models\Liga;
use App\Support\LigaActual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CampeonatoController extends Controller
{
    // Cualquier usuario logueado puede crear su campeonato.
    public function create()
    {
        return view('campeonatos.create');
    }

    public function store(Request $request)
    {
        $data = $this->validar($request);
        $user = $request->user();

        $liga = DB::transaction(function () use ($data, $user) {
            $liga = Liga::create($data + [
                'slug' => Liga::slugDisponible($data['nombre']),
                'owner_id' => $user->id,
                'activa' => true,
            ]);

            // Quien lo crea es el dueño: administra solo este campeonato.
            $liga->usuarios()->attach($user->id, ['rol' => 'owner']);

            return $liga;
        });

        $this->abrir($request, $liga);

        return redirect()->route('configuracion.index')
            ->with('status', "Campeonato \"{$liga->nombre}\" creado. Ahora agrega tus canchas y equipos, y genera el fixture.");
    }

    // Cambia el campeonato en el que se está trabajando.
    public function usar(Request $request, Liga $liga)
    {
        abort_unless(in_array($liga->id, $request->user()->ligasIds(), true), 403);

        $this->abrir($request, $liga);

        return redirect()->route('dashboard')->with('status', "Ahora estás en \"{$liga->nombre}\".");
    }

    // Configuración del campeonato actual (dueño o admin).
    public function edit(Request $request)
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        return view('campeonatos.edit', ['liga' => LigaActual::liga()]);
    }

    public function update(Request $request)
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        LigaActual::liga()->update($this->validar($request));

        return redirect()->route('campeonatos.edit')->with('status', 'Campeonato actualizado.');
    }

    protected function abrir(Request $request, Liga $liga): void
    {
        $request->session()->put('liga_id', $liga->id);
        $request->user()->forceFill(['ultima_liga_id' => $liga->id])->save();
    }

    protected function validar(Request $request): array
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'publica' => ['boolean'],
        ]);

        $data['publica'] = $request->boolean('publica');

        return $data;
    }
}
