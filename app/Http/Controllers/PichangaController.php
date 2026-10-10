<?php

namespace App\Http\Controllers;

use App\Models\Pichanga;
use App\Models\PichangaAsistencia;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PichangaController extends Controller
{
    // Mis pichangas (como organizador o jugador).
    public function index(Request $request)
    {
        $pichangas = $request->user()->pichangas()
            ->withCount('miembros')
            ->orderByDesc('activa')
            ->orderBy('dia_semana')
            ->get();

        return view('pichangas.index', compact('pichangas'));
    }

    // Cualquier usuario logueado puede crear una pichanga.
    public function create()
    {
        return view('pichangas.create');
    }

    public function store(Request $request)
    {
        $data = $this->validar($request);

        $pichanga = DB::transaction(function () use ($data, $request) {
            $pichanga = Pichanga::create($data + [
                'organizador_id' => $request->user()->id,
                'token' => Str::random(40),
            ]);

            $pichanga->miembros()->attach($request->user()->id, ['rol' => 'organizador']);

            return $pichanga;
        });

        return redirect()->route('pichangas.show', $pichanga)
            ->with('status', 'Pichanga creada. Comparte el link de invitación con tus amigos.');
    }

    // Próxima fecha con "Voy / No voy", listas y el historial.
    public function show(Request $request, Pichanga $pichanga)
    {
        $user = $request->user();
        abort_unless($pichanga->esMiembro($user), 403);

        $fecha = $pichanga->activa ? $pichanga->proximaFecha() : null;
        $fecha?->load('asistencias.user');

        $miAsistencia = $fecha?->asistencias->firstWhere('user_id', $user->id);

        // Incluye fechas pasadas que quedaron sin resultado, para poder completarlas.
        $historial = $pichanga->fechas()
            ->where(fn ($q) => $q->whereIn('estado', ['jugada', 'cancelada'])->orWhereDate('fecha', '<', today()))
            ->orderByDesc('fecha')
            ->limit(10)
            ->get();

        // Goleadores y asistencia acumulada de la pichanga.
        $ranking = PichangaAsistencia::query()
            ->join('pichanga_fechas', 'pichanga_fechas.id', '=', 'pichanga_asistencias.pichanga_fecha_id')
            ->where('pichanga_fechas.pichanga_id', $pichanga->id)
            ->where('pichanga_fechas.estado', 'jugada')
            ->where('pichanga_asistencias.estado', 'confirmado')
            ->groupBy('pichanga_asistencias.user_id')
            ->select('pichanga_asistencias.user_id')
            ->selectRaw('COUNT(*) AS partidos, SUM(pichanga_asistencias.goles) AS goles')
            ->orderByDesc('goles')
            ->orderByDesc('partidos')
            ->limit(10)
            ->with('user')
            ->get();

        $miembros = $pichanga->miembros()->orderBy('name')->get();
        $esOrganizador = $pichanga->esOrganizador($user);

        return view('pichangas.show', compact(
            'pichanga', 'fecha', 'miAsistencia', 'historial', 'ranking', 'miembros', 'esOrganizador'
        ));
    }

    public function edit(Request $request, Pichanga $pichanga)
    {
        abort_unless($pichanga->esOrganizador($request->user()), 403);

        return view('pichangas.edit', compact('pichanga'));
    }

    public function update(Request $request, Pichanga $pichanga)
    {
        abort_unless($pichanga->esOrganizador($request->user()), 403);

        $pichanga->update($this->validar($request) + ['activa' => $request->boolean('activa')]);

        return redirect()->route('pichangas.show', $pichanga)->with('status', 'Pichanga actualizada.');
    }

    // Link de invitación: confirma antes de unirse.
    public function unirseForm(Request $request, string $token)
    {
        $pichanga = Pichanga::where('token', $token)->where('activa', true)->firstOrFail();

        if ($pichanga->esMiembro($request->user())) {
            return redirect()->route('pichangas.show', $pichanga);
        }

        $pichanga->loadCount('miembros')->load('organizador');

        return view('pichangas.unirse', compact('pichanga'));
    }

    public function unirse(Request $request, string $token)
    {
        $pichanga = Pichanga::where('token', $token)->where('activa', true)->firstOrFail();

        $pichanga->miembros()->syncWithoutDetaching([$request->user()->id => ['rol' => 'jugador']]);

        return redirect()->route('pichangas.show', $pichanga)->with('status', '¡Te uniste a la pichanga!');
    }

    // El organizador saca a alguien, o un jugador se sale solo.
    public function quitarMiembro(Request $request, Pichanga $pichanga, User $user)
    {
        $yo = $request->user();
        abort_unless($yo->id === $user->id || $pichanga->esOrganizador($yo), 403);

        if ($pichanga->esOrganizador($user)) {
            return back()->withErrors(['miembro' => 'El organizador no puede salir de su propia pichanga.']);
        }

        $pichanga->miembros()->detach($user->id);

        return $yo->id === $user->id
            ? redirect()->route('pichangas.index')->with('status', 'Saliste de la pichanga.')
            : back()->with('status', 'Jugador quitado de la pichanga.');
    }

    protected function validar(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'lugar' => ['nullable', 'string', 'max:200'],
            'dia_semana' => ['required', 'integer', Rule::in(array_keys(Pichanga::DIAS))],
            'hora' => ['required', 'date_format:H:i'],
            'duracion_min' => ['required', 'integer', 'min:30', 'max:240'],
            'cupo' => ['required', 'integer', 'min:2', 'max:50'],
            'precio' => ['nullable', 'integer', 'min:0'],
        ]);
    }
}
