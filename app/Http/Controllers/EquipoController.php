<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\Modalidad;
use App\Support\LigaActual;
use Illuminate\Http\Request;

class EquipoController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Equipo::class);

        $equipos = Equipo::with('modalidad')
            ->withCount('jugadores')
            ->orderBy('nombre')
            ->get();

        return view('equipos.index', compact('equipos'));
    }

    public function create()
    {
        $this->authorize('create', Equipo::class);

        if ($limite = $this->limiteAlcanzado()) {
            return $limite;
        }

        $modalidades = Modalidad::orderBy('nombre')->get();

        return view('equipos.create', compact('modalidades'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Equipo::class);

        if ($limite = $this->limiteAlcanzado()) {
            return $limite;
        }

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:120', LigaActual::unico('equipos', 'nombre')],
            'modalidad_id' => ['required', 'exists:modalidades,id'],
            'color_camiseta' => ['nullable', 'string', 'max:30'],
            'escudo' => ['nullable', 'image', 'max:2048'],
            'foto' => ['nullable', 'image', 'max:2048'],
        ]);

        $equipo = Equipo::create([
            'nombre' => $data['nombre'],
            'modalidad_id' => $data['modalidad_id'],
            'color_camiseta' => $data['color_camiseta'] ?? null,
        ]);

        $this->guardarImagen($request, $equipo, 'escudo', 'equipos', 'escudo');
        $this->guardarImagen($request, $equipo, 'foto', 'equipos', 'foto');

        return redirect()->route('equipos.show', $equipo)
            ->with('status', 'Equipo creado. Ya puedes generar el link para su DT.');
    }

    public function show(Equipo $equipo)
    {
        $this->authorize('view', $equipo);

        $equipo->load('modalidad', 'jugadores', 'dt', 'invitaciones');
        $insignias = \App\Support\Logros\Insignias::mejores($equipo->jugadores->pluck('id'));

        return view('equipos.show', compact('equipo', 'insignias'));
    }

    public function edit(Equipo $equipo)
    {
        $this->authorize('update', $equipo);

        $modalidades = Modalidad::orderBy('nombre')->get();

        return view('equipos.edit', compact('equipo', 'modalidades'));
    }

    public function update(Request $request, Equipo $equipo)
    {
        $this->authorize('update', $equipo);

        $esAdmin = $request->user()->hasRole('admin');

        $reglas = [
            'color_camiseta' => ['nullable', 'string', 'max:30'],
            'escudo' => ['nullable', 'image', 'max:2048'],
            'foto' => ['nullable', 'image', 'max:2048'],
        ];

        if ($esAdmin) {
            $reglas['nombre'] = ['required', 'string', 'max:120', LigaActual::unico('equipos', 'nombre', $equipo->id)];
            $reglas['modalidad_id'] = ['required', 'exists:modalidades,id'];
        }

        $data = $request->validate($reglas);

        $campos = ['color_camiseta' => $data['color_camiseta'] ?? null];
        if ($esAdmin) {
            $campos['nombre'] = $data['nombre'];
            $campos['modalidad_id'] = $data['modalidad_id'];
        }
        $equipo->update($campos);

        $this->guardarImagen($request, $equipo, 'escudo', 'equipos', 'escudo');
        $this->guardarImagen($request, $equipo, 'foto', 'equipos', 'foto');

        return redirect()->route('equipos.show', $equipo)->with('status', 'Equipo actualizado.');
    }

    // Plan Gratis: hasta Liga::EQUIPOS_PLAN_GRATIS equipos. Al pasarse, a la página del plan.
    protected function limiteAlcanzado()
    {
        $liga = LigaActual::liga();

        if ($liga->puedeAgregarEquipo()) {
            return null;
        }

        return redirect()->route('campeonatos.plan')->with('aviso',
            'El plan Gratis permite hasta '.$liga->limiteEquipos().' equipos. Pasa a Pro para agregar más.');
    }

    // Guarda un archivo en public_html/uploads/{carpeta} y actualiza el campo del modelo.
    // Devuelve la ruta relativa (ej. uploads/equipos/escudo_15_1699999999.jpg).
    protected function guardarImagen(Request $request, Equipo $equipo, string $campo, string $carpeta, string $prefijo): void
    {
        if (! $request->hasFile($campo)) {
            return;
        }

        $file = $request->file($campo);
        $dir = public_path('uploads/'.$carpeta);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Borrar la imagen anterior si existía.
        if ($equipo->$campo && is_file(public_path($equipo->$campo))) {
            @unlink(public_path($equipo->$campo));
        }

        $nombre = $prefijo.'_'.$equipo->id.'_'.time().'.'.$file->getClientOriginalExtension();
        $file->move($dir, $nombre);

        $equipo->update([$campo => 'uploads/'.$carpeta.'/'.$nombre]);
    }
}
