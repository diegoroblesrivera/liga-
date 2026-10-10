<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\Jugador;
use App\Models\Role;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JugadorController extends Controller
{
    // Listado global de jugadores (todos los equipos), para que el admin pueda
    // ubicar y editar cualquier ficha (foto incluida) sin entrar equipo por equipo.
    public function index(Request $request)
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $busqueda = trim((string) $request->query('q', ''));

        $jugadores = Jugador::with('equipo')
            ->when($busqueda !== '', function ($query) use ($busqueda) {
                $query->where('nombre', 'like', "%{$busqueda}%")
                    ->orWhereHas('equipo', fn ($q) => $q->where('nombre', 'like', "%{$busqueda}%"));
            })
            ->get()
            ->sortBy([
                fn ($j) => $j->equipo?->nombre ?? '',
                fn ($j) => $j->nombre,
            ])
            ->groupBy(fn ($j) => $j->equipo?->nombre ?? 'Sin equipo');

        return view('jugadores.index', compact('jugadores', 'busqueda'));
    }

    public function edit(Jugador $jugador)
    {
        $this->authorize('update', $jugador);

        $jugador->load('equipo');

        return view('jugadores.edit', compact('jugador'));
    }

    public function update(Request $request, Jugador $jugador)
    {
        $this->authorize('update', $jugador);

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'dorsal' => ['nullable', 'integer', 'min:1', 'max:99'],
            'posicion' => ['nullable', 'string', 'max:40'],
            'foto' => ['nullable', 'image', 'max:2048'],
        ]);

        $jugador->update([
            'nombre' => $data['nombre'],
            'dorsal' => $data['dorsal'] ?? null,
            'posicion' => $data['posicion'] ?? null,
        ]);

        $this->guardarFoto($request, $jugador);

        return redirect()->route('equipos.show', $jugador->equipo_id)
            ->with('status', 'Jugador actualizado.');
    }

    public function destroy(Jugador $jugador)
    {
        $this->authorize('delete', $jugador);

        // Borrar su foto si tenía.
        if ($jugador->foto && is_file(public_path($jugador->foto))) {
            @unlink(public_path($jugador->foto));
        }

        $equipoId = $jugador->equipo_id;
        $jugador->delete();

        return redirect()->route('equipos.show', $equipoId)
            ->with('status', 'Jugador dado de baja.');
    }

    public function unirme(Request $request, Equipo $equipo)
    {
        $this->authorize('create', [Jugador::class, $equipo]);

        $user = $request->user();

        if ($user->jugador) {
            return back()->withErrors(['jugar' => 'Ya tienes una ficha de jugador.']);
        }

        if (! $equipo->tieneCupoDisponible()) {
            return back()->withErrors(['jugar' => 'El equipo ya alcanzó su cupo máximo de jugadores.']);
        }

        $data = $request->validate([
            'dorsal' => ['nullable', 'integer', 'min:1', 'max:99'],
            'posicion' => ['nullable', 'string', 'max:40'],
        ]);

        try {
            DB::transaction(function () use ($user, $equipo, $data) {
                if (! $user->hasRole('jugador')) {
                    $user->roles()->attach(Role::where('nombre', 'jugador')->firstOrFail()->id);
                }

                Jugador::create([
                    'equipo_id' => $equipo->id,
                    'user_id' => $user->id,
                    'nombre' => $user->name,
                    'dorsal' => $data['dorsal'] ?? null,
                    'posicion' => $data['posicion'] ?? null,
                ]);
            });
        } catch (QueryException $e) {
            return back()->withErrors(['jugar' => 'El equipo ya alcanzó su cupo máximo de jugadores.']);
        }

        return back()->with('status', 'Te sumaste como jugador del equipo.');
    }

    // Guarda la foto en public_html/uploads/jugadores y actualiza el campo.
    protected function guardarFoto(Request $request, Jugador $jugador): void
    {
        if (! $request->hasFile('foto')) {
            return;
        }

        $file = $request->file('foto');
        $dir = public_path('uploads/jugadores');

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if ($jugador->foto && is_file(public_path($jugador->foto))) {
            @unlink(public_path($jugador->foto));
        }

        $nombre = 'jugador_'.$jugador->id.'_'.time().'.'.$file->getClientOriginalExtension();
        $file->move($dir, $nombre);

        $jugador->update(['foto' => 'uploads/jugadores/'.$nombre]);
    }
}
