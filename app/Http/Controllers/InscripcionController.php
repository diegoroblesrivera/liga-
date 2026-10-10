<?php

namespace App\Http\Controllers;

use App\Models\Invitacion;
use App\Models\Jugador;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

class InscripcionController extends Controller
{
    // Muestra el formulario de registro asociado a la invitación.
    public function show(string $token)
    {
        $invitacion = $this->invitacionValida($token);

        return view('inscripcion.show', [
            'invitacion' => $invitacion,
            'equipo' => $invitacion->equipo,
        ]);
    }

    // Procesa el registro: crea el usuario y, si corresponde, su ficha de jugador.
    public function store(Request $request, string $token)
    {
        $invitacion = $this->invitacionValida($token);
        $equipo = $invitacion->equipo;

        // ¿Esta persona tendrá ficha de jugador?
        // Jugador: siempre. DT: solo si marcó la casilla. Árbitro: nunca.
        $jugar = $invitacion->tipo === 'jugador'
            || ($invitacion->tipo === 'dt' && $request->boolean('jugar'));

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
            $user = DB::transaction(function () use ($data, $invitacion, $equipo, $jugar) {
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => $data['password'], // se hashea por el cast 'hashed'
                    // El DT queda vinculado a su equipo; el jugador no usa equipo_id.
                    'equipo_id' => $invitacion->tipo === 'dt' ? $equipo->id : null,
                ]);

                // Rol principal según el tipo de invitación.
                $user->roles()->attach(Role::where('nombre', $invitacion->tipo)->firstOrFail()->id);

                if ($jugar) {
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

        return redirect()->route('dashboard');
    }

    // Valida el token: 404 si no existe, 410 si ya no es utilizable.
    protected function invitacionValida(string $token): Invitacion
    {
        $invitacion = Invitacion::where('token', $token)->firstOrFail();

        abort_unless($invitacion->esValida(), 410, 'Esta invitación ya no es válida.');

        return $invitacion;
    }
}
