<?php

namespace App\Http\Controllers;

use App\Exports\PlantillaExport;
use App\Mail\InvitacionEquipo;
use App\Models\Equipo;
use App\Models\Invitacion;
use App\Models\Jugador;
use App\Models\Modalidad;
use App\Models\User;
use App\Support\LigaActual;
use App\Support\Planilla;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

// Carga masiva por Excel: equipos con su DT (organizador) y plantel (DT del equipo).
// Todo o nada: si una fila tiene errores no se crea nada y se listan los problemas.
class CargaMasivaController extends Controller
{
    private const COLUMNAS_EQUIPOS = [
        'equipo' => ['Equipo', 'Nombre del equipo', 'Nombre equipo'],
        'dt' => ['Nombre DT', 'DT', 'Nombre del DT', 'Entrenador', 'Director tecnico'],
        'email' => ['Email DT', 'Correo DT', 'Mail DT', 'Email', 'Correo'],
        'telefono' => ['Telefono DT', 'Telefono', 'Celular', 'Fono'],
    ];

    private const COLUMNAS_JUGADORES = [
        'nombre' => ['Nombre', 'Jugador', 'Nombre jugador', 'Nombre del jugador'],
        'dorsal' => ['Dorsal', 'Numero', 'N', 'Camiseta', 'Numero camiseta'],
        'posicion' => ['Posicion', 'Puesto'],
        'email' => ['Email', 'Correo', 'Mail'],
    ];

    // ---------------------------------------------------------------- equipos

    public function equipos()
    {
        $this->authorize('create', Equipo::class);

        $liga = LigaActual::liga();

        return view('carga-masiva.equipos', [
            'modalidades' => Modalidad::orderBy('nombre')->get(),
            'equipos' => $liga->equipos()->count(),
            'limite' => $liga->limiteEquipos(),
        ]);
    }

    public function plantillaEquipos()
    {
        $this->authorize('create', Equipo::class);

        return Excel::download(new PlantillaExport(
            ['Equipo', 'Nombre DT', 'Email DT', 'Teléfono DT (opcional)'],
            [['Los Leones', 'Juan Pérez', 'juan.perez@correo.cl', '+56 9 1234 5678']]
        ), 'plantilla-equipos.xlsx');
    }

    public function importarEquipos(Request $request)
    {
        $this->authorize('create', Equipo::class);

        $request->validate([
            'archivo' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:2048'],
            'modalidad_id' => ['required', 'exists:modalidades,id'],
        ]);

        $leido = Planilla::leer($request->file('archivo'), self::COLUMNAS_EQUIPOS, ['equipo', 'dt', 'email']);
        if ($error = $this->errorDeLectura($leido, ['equipo' => 'Equipo', 'dt' => 'Nombre DT', 'email' => 'Email DT'])) {
            return back()->withErrors(['archivo' => $error]);
        }
        $filas = $leido['filas'];

        $liga = LigaActual::liga();
        $existentes = $liga->equipos()->pluck('nombre')->map(fn ($n) => Str::lower(trim($n)))->all();
        $errores = [];
        $vistos = [];

        foreach ($filas as $n => $f) {
            $v = Validator::make($f, [
                'equipo' => ['required', 'string', 'max:120'],
                'dt' => ['required', 'string', 'max:120'],
                'email' => ['required', 'email', 'max:180'],
                'telefono' => ['nullable', 'string', 'max:30'],
            ], [], ['equipo' => 'equipo', 'dt' => 'nombre del DT', 'email' => 'email del DT', 'telefono' => 'teléfono']);
            foreach ($v->errors()->all() as $msg) {
                $errores[$n][] = $msg;
            }

            $clave = Str::lower(trim((string) $f['equipo']));
            if ($clave !== '' && in_array($clave, $existentes, true)) {
                $errores[$n][] = "Ya existe un equipo llamado \"{$f['equipo']}\" en el campeonato.";
            } elseif ($clave !== '' && isset($vistos[$clave])) {
                $errores[$n][] = "El equipo \"{$f['equipo']}\" está repetido (fila {$vistos[$clave]}).";
            }
            $vistos[$clave] ??= $n;
        }

        $limite = $liga->limiteEquipos();
        if ($limite !== null && count($existentes) + count($filas) > $limite) {
            return back()->withErrors(['archivo' => "El plan Gratis permite hasta {$limite} equipos: ya tienes ".count($existentes).' y el archivo trae '.count($filas).'. Pasa a Pro para agregar más.']);
        }

        if ($errores) {
            return back()->with('errores_filas', $errores);
        }

        $invitaciones = DB::transaction(function () use ($filas, $request) {
            return collect($filas)->map(function ($f) use ($request) {
                $equipo = Equipo::create(['nombre' => trim($f['equipo']), 'modalidad_id' => $request->modalidad_id]);

                return Invitacion::create([
                    'equipo_id' => $equipo->id,
                    'tipo' => 'dt',
                    'token' => Str::random(40),
                    'email' => Str::lower($f['email']),
                    'nombre' => $f['dt'],
                    'telefono' => $f['telefono'],
                    'reutilizable' => false,
                    'creada_por' => $request->user()->id,
                ]);
            });
        });

        $enviados = $this->enviar($invitaciones, $request->user());

        return redirect()->route('equipos.index')->with('status',
            "Se crearon {$invitaciones->count()} equipos. Se enviaron {$enviados} invitaciones a sus DT"
            .($enviados < $invitaciones->count() ? ' (algunas fallaron: puedes reenviarlas desde cada equipo).' : '.'));
    }

    // ---------------------------------------------------------------- jugadores

    public function jugadores(Equipo $equipo)
    {
        $this->authorize('create', [Jugador::class, $equipo]);

        $equipo->load('modalidad');

        return view('carga-masiva.jugadores', [
            'equipo' => $equipo,
            'jugadores' => $equipo->jugadores()->count(),
            'cupo' => $equipo->cupoMaximo(),
        ]);
    }

    public function plantillaJugadores(Equipo $equipo)
    {
        $this->authorize('create', [Jugador::class, $equipo]);

        return Excel::download(new PlantillaExport(
            ['Nombre', 'Dorsal', 'Posición', 'Email'],
            [['Pedro González', 10, 'Delantero', 'pedro.gonzalez@correo.cl'], ['Diego Soto', 1, 'Arquero', '']]
        ), 'plantilla-jugadores-'.Str::slug($equipo->nombre).'.xlsx');
    }

    public function importarJugadores(Request $request, Equipo $equipo)
    {
        $this->authorize('create', [Jugador::class, $equipo]);

        $request->validate(['archivo' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:2048']]);

        $leido = Planilla::leer($request->file('archivo'), self::COLUMNAS_JUGADORES, ['nombre']);
        if ($error = $this->errorDeLectura($leido, ['nombre' => 'Nombre'])) {
            return back()->withErrors(['archivo' => $error]);
        }
        $filas = $leido['filas'];

        $plantel = $equipo->jugadores()->get();
        $dorsales = $plantel->pluck('dorsal')->filter()->map(fn ($d) => (int) $d)->all();
        $correosEnEquipo = User::whereIn('id', $plantel->pluck('user_id')->filter())->pluck('email')->map(fn ($e) => Str::lower($e))->all();
        $correosInvitados = Invitacion::where('equipo_id', $equipo->id)->whereNotNull('jugador_id')->pluck('email')->map(fn ($e) => Str::lower($e))->all();
        // Personas del archivo que ya juegan en otro equipo de este campeonato.
        $enOtroEquipo = User::whereIn(DB::raw('LOWER(email)'), collect($filas)->pluck('email')->filter()->map(fn ($e) => Str::lower($e)))->get(['id', 'email'])
            ->mapWithKeys(fn ($u) => [Str::lower($u->email) => Jugador::equipoEnLiga($u->id, $equipo->liga_id)])
            ->filter(fn ($otro) => $otro && $otro->id !== $equipo->id);
        $errores = [];
        $vistosDorsal = [];
        $vistosEmail = [];

        foreach ($filas as $n => $f) {
            $v = Validator::make($f, [
                'nombre' => ['required', 'string', 'max:120'],
                'dorsal' => ['nullable', 'integer', 'min:1', 'max:99'],
                'posicion' => ['nullable', 'string', 'max:40'],
                'email' => ['nullable', 'email', 'max:180'],
            ], [], ['nombre' => 'nombre', 'dorsal' => 'dorsal', 'posicion' => 'posición', 'email' => 'email']);
            foreach ($v->errors()->all() as $msg) {
                $errores[$n][] = $msg;
            }

            if ($f['dorsal'] !== null && ctype_digit((string) $f['dorsal'])) {
                $d = (int) $f['dorsal'];
                if (in_array($d, $dorsales, true)) {
                    $errores[$n][] = "La camiseta #{$d} ya la usa otro jugador del equipo.";
                } elseif (isset($vistosDorsal[$d])) {
                    $errores[$n][] = "La camiseta #{$d} está repetida (fila {$vistosDorsal[$d]}).";
                }
                $vistosDorsal[$d] ??= $n;
            }

            if ($f['email'] !== null) {
                $e = Str::lower($f['email']);
                if (in_array($e, $correosEnEquipo, true) || in_array($e, $correosInvitados, true)) {
                    $errores[$n][] = "{$f['email']} ya está en el plantel o invitado.";
                } elseif ($enOtroEquipo->has($e)) {
                    $errores[$n][] = "{$f['email']} ya juega en {$enOtroEquipo[$e]->nombre} (un jugador solo puede estar en un equipo por campeonato).";
                } elseif (isset($vistosEmail[$e])) {
                    $errores[$n][] = "El email {$f['email']} está repetido (fila {$vistosEmail[$e]}).";
                }
                $vistosEmail[$e] ??= $n;
            }
        }

        $cupo = $equipo->cupoMaximo();
        if ($cupo !== null && $plantel->count() + count($filas) > $cupo) {
            return back()->withErrors(['archivo' => "El equipo admite hasta {$cupo} jugadores: ya tiene {$plantel->count()} y el archivo trae ".count($filas).'.']);
        }

        if ($errores) {
            return back()->with('errores_filas', $errores);
        }

        $invitaciones = DB::transaction(function () use ($filas, $equipo, $request) {
            $invitaciones = collect();
            foreach ($filas as $f) {
                $ficha = Jugador::create([
                    'equipo_id' => $equipo->id,
                    'nombre' => $f['nombre'],
                    'dorsal' => $f['dorsal'] !== null ? (int) $f['dorsal'] : null,
                    'posicion' => $f['posicion'],
                ]);

                if ($f['email'] !== null) {
                    $invitaciones->push(Invitacion::create([
                        'equipo_id' => $equipo->id,
                        'jugador_id' => $ficha->id,
                        'tipo' => 'jugador',
                        'token' => Str::random(40),
                        'email' => Str::lower($f['email']),
                        'nombre' => $f['nombre'],
                        'reutilizable' => false,
                        'creada_por' => $request->user()->id,
                    ]));
                }
            }

            return $invitaciones;
        });

        $enviados = $this->enviar($invitaciones, $request->user());

        return redirect()->route('equipos.show', $equipo)->with('status',
            'Se agregaron '.count($filas).' jugadores al plantel'
            .($invitaciones->isNotEmpty() ? " y se enviaron {$enviados} invitaciones por correo." : '.'));
    }

    // ---------------------------------------------------------------- comunes

    // Reenviar el correo de una invitación con email (organizador, o el DT las de su equipo).
    public function reenviar(Request $request, Invitacion $invitacion)
    {
        $this->authorize('delete', $invitacion);
        abort_unless($invitacion->email && $invitacion->esValida(), 422, 'Esta invitación no se puede reenviar.');

        $enviados = $this->enviar(collect([$invitacion]), $request->user());

        return back()->with('status', $enviados ? "Invitación reenviada a {$invitacion->email}." : 'No se pudo enviar el correo. Intenta de nuevo en unos minutos.');
    }

    private function enviar($invitaciones, User $remitente): int
    {
        $liga = LigaActual::liga()->nombre;
        $enviados = 0;

        foreach ($invitaciones as $inv) {
            try {
                Mail::to($inv->email)->send(new InvitacionEquipo($inv, $inv->equipo->nombre, $liga, $remitente->name));
                $inv->forceFill(['enviada_en' => now()])->save();
                $enviados++;
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $enviados;
    }

    private function errorDeLectura(array $leido, array $nombres): ?string
    {
        if ($leido['ilegible']) {
            return 'No pudimos leer el archivo. Usa la plantilla en formato Excel (.xlsx).';
        }
        if ($leido['faltan']) {
            return 'Faltan columnas en el archivo: '.collect($leido['faltan'])->map(fn ($c) => $nombres[$c])->join(', ', ' y ').'. Usa la plantilla.';
        }
        if (! $leido['filas']) {
            return 'El archivo no trae filas para cargar.';
        }

        return null;
    }
}
