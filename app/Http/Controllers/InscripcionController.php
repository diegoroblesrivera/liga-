<?php

namespace App\Http\Controllers;

use App\Models\Invitacion;
use App\Models\Jugador;
use App\Models\Role;
use App\Models\User;
use App\Support\LigaActual;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

class InscripcionController extends Controller
{
    // Muestra el registro asociado a la invitación, o "Aceptar" si ya inició sesión.
    public function show(Request $request, string $token)
    {
        $invitacion = $this->invitacionValida($token);

        // Si inicia sesión (correo o Google) vuelve acá para aceptar.
        if (! $request->user()) {
            $request->session()->put('url.intended', route('invitacion.show', $token));
        }

        // El link puede ser de un campeonato distinto al que el usuario tiene abierto.
        return LigaActual::usar($invitacion->liga, fn () => view('inscripcion.show', [
            'invitacion' => $invitacion,
            'equipo' => $invitacion->equipo,
            'ficha' => $invitacion->jugador,
        ])->render());
    }

    // Procesa el registro: crea el usuario y, si corresponde, su ficha de jugador.
    public function store(Request $request, string $token)
    {
        $invitacion = $this->invitacionValida($token);

        return LigaActual::usar($invitacion->liga, fn () => $this->registrar($request, $invitacion));
    }

    // Usuario que ya tiene cuenta: acepta la invitación con su sesión.
    public function aceptar(Request $request, string $token)
    {
        $invitacion = $this->invitacionValida($token);

        return LigaActual::usar($invitacion->liga, fn () => $this->vincular($request, $request->user(), $invitacion));
    }

    protected function registrar(Request $request, Invitacion $invitacion)
    {
        $equipo = $invitacion->equipo;
        $ficha = $invitacion->jugador;

        // ¿Esta persona tendrá ficha de jugador nueva?
        // Jugador: siempre (salvo que reclame una ya cargada). DT: solo si marcó la casilla. Árbitro: nunca.
        $jugar = ! $ficha && ($invitacion->tipo === 'jugador'
            || ($invitacion->tipo === 'dt' && $request->boolean('jugar')));

        if ($jugar && ! $equipo->tieneCupoDisponible()) {
            return back()->withErrors(['cupo' => 'El equipo ya alcanzó su cupo máximo de jugadores.']);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'dorsal' => ['nullable', 'integer', 'min:1', 'max:99'],
            'posicion' => ['nullable', 'string', 'max:40'],
        ]);

        try {
            $user = DB::transaction(function () use ($data, $invitacion, $equipo, $jugar, $ficha) {
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => $data['password'], // se hashea por el cast 'hashed'
                    // El DT queda vinculado a su equipo; el jugador no usa equipo_id.
                    'equipo_id' => $invitacion->tipo === 'dt' ? $equipo->id : null,
                ]);

                // Rol principal según el tipo de invitación.
                $user->roles()->attach(Role::where('nombre', $invitacion->tipo)->firstOrFail()->id);

                // El árbitro lo es de ESTE campeonato (DT y jugador quedan ligados por su equipo).
                if ($invitacion->tipo === 'arbitro') {
                    $user->ligas()->attach($invitacion->liga_id, ['rol' => 'arbitro']);
                }

                $user->forceFill(['ultima_liga_id' => $invitacion->liga_id, 'telefono' => $invitacion->telefono])->save();

                if ($ficha) {
                    $ficha->update(['user_id' => $user->id]);
                } elseif ($jugar) {
                    // Un DT que además juega suma el rol jugador.
                    if ($invitacion->tipo === 'dt') {
                        $user->roles()->attach(Role::where('nombre', 'jugador')->firstOrFail()->id);
                    }

                    Jugador::create([
                        'equipo_id' => $equipo->id,
                        'user_id' => $user->id,
                        'nombre' => $data['name'],
                        'dorsal' => $data['dorsal'] ?? null,
                        'posicion' => $data['posicion'] ?? null,
                    ]);
                }

                $invitacion->increment('usos');

                return $user;
            });
        } catch (QueryException $e) {
            // Respaldo: el trigger de cupo pudo rechazar el alta en una carrera concurrente.
            return back()->withErrors(['cupo' => 'El equipo ya alcanzó su cupo máximo de jugadores.']);
        }

        event(new Registered($user));
        Auth::login($user);
        $request->session()->put('liga_id', $invitacion->liga_id);

        return $this->despues($invitacion, $ficha);
    }

    // Vincula una cuenta existente: DT de su equipo, ficha cargada por el DT o jugador nuevo.
    protected function vincular(Request $request, User $user, Invitacion $invitacion)
    {
        $equipo = $invitacion->equipo;
        $ficha = $invitacion->jugador;
        $rol = fn (string $nombre) => $user->roles()->syncWithoutDetaching([Role::where('nombre', $nombre)->firstOrFail()->id]);

        if ($invitacion->tipo === 'dt') {
            if ($user->equipo_id && $user->equipo_id !== $equipo->id) {
                return back()->withErrors(['cupo' => 'Tu cuenta ya es DT de otro equipo. Entra con otra cuenta o pídele al organizador que lo cambie.']);
            }
            DB::transaction(function () use ($user, $equipo, $invitacion, $rol) {
                $user->forceFill(['equipo_id' => $equipo->id, 'telefono' => $user->telefono ?? $invitacion->telefono])->save();
                $rol('dt');
                $invitacion->increment('usos');
            });
        } elseif ($invitacion->tipo === 'arbitro') {
            DB::transaction(function () use ($user, $invitacion, $rol) {
                $user->ligas()->syncWithoutDetaching([$invitacion->liga_id => ['rol' => 'arbitro']]);
                $rol('arbitro');
                $invitacion->increment('usos');
            });
        } else {
            if ($otro = Jugador::equipoEnLiga($user->id, $invitacion->liga_id, $ficha?->id)) {
                return back()->withErrors(['cupo' => $otro->id === $equipo->id ? 'Ya estás en el plantel de este equipo.' : Jugador::mensajeOtroEquipo($otro)]);
            }
            if (! $ficha && ! $equipo->tieneCupoDisponible()) {
                return back()->withErrors(['cupo' => 'El equipo ya alcanzó su cupo máximo de jugadores.']);
            }
            DB::transaction(function () use ($user, $equipo, $ficha, $invitacion, $rol) {
                $ficha
                    ? $ficha->update(['user_id' => $user->id])
                    : Jugador::create(['equipo_id' => $equipo->id, 'user_id' => $user->id, 'nombre' => $user->name]);
                $rol('jugador');
                $invitacion->increment('usos');
            });
        }

        $user->forceFill(['ultima_liga_id' => $invitacion->liga_id])->save();
        $request->session()->put('liga_id', $invitacion->liga_id);

        return $this->despues($invitacion, $ficha);
    }

    // Después de inscribirse: el DT completa su equipo; el jugador con ficha, su foto.
    protected function despues(Invitacion $invitacion, ?Jugador $ficha)
    {
        if ($invitacion->tipo === 'dt') {
            return redirect()->route('equipos.edit', $invitacion->equipo_id)
                ->with('status', '¡Listo! Ya eres DT del equipo. Completa sus datos: color de camiseta, escudo y foto.');
        }

        if ($ficha) {
            return redirect()->route('jugadores.edit', $ficha->id)
                ->with('status', '¡Listo! Ya estás en el plantel. Revisa tus datos y sube tu foto.');
        }

        return redirect()->route('dashboard');
    }

    // Valida el token: 404 si no existe, 410 si ya no es utilizable.
    // Sin el filtro por campeonato: el token es lo que da acceso.
    protected function invitacionValida(string $token): Invitacion
    {
        $invitacion = Invitacion::withoutGlobalScopes()->where('token', $token)->firstOrFail();

        abort_unless($invitacion->esValida(), 410, 'Esta invitación ya no es válida.');
        abort_if($invitacion->jugador_id && ! $invitacion->jugador, 410, 'Esta invitación ya no es válida.');

        return $invitacion;
    }
}
