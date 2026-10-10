<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\Torneo;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class FixtureController extends Controller
{
    // Formulario para generar el fixture (solo admin).
    public function create(Request $request)
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $totalEquipos = Equipo::count();

        return view('fixtures.create', compact('totalEquipos'));
    }

    // Crea un torneo con todos los equipos y genera el todos-contra-todos
    // ida y vuelta, calendarizado según los parámetros del formulario.
    public function store(Request $request)
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'temporada' => ['nullable', 'string', 'max:20'],
            'fecha_inicio' => ['required', 'date'],
            'dias_semana' => ['required', 'array', 'min:1'],
            'dias_semana.*' => ['integer', 'between:0,6'],
            'partidos_por_dia' => ['required', 'integer', 'min:1', 'max:50'],
            'hora_defecto' => ['required', 'date_format:H:i'],
        ], [
            'dias_semana.required' => 'Elige al menos un día de la semana.',
            'hora_defecto.date_format' => 'La hora debe tener el formato HH:MM (por ejemplo, 22:00).',
        ]);

        $equipos = Equipo::orderBy('id')->pluck('id')->all();

        if (count($equipos) < 2) {
            return back()->withErrors(['nombre' => 'Necesitas al menos 2 equipos creados para generar un fixture.']);
        }

        $inicio = Carbon::parse($data['fecha_inicio'])->startOfDay();
        $dias = collect($data['dias_semana'])->map(fn ($d) => (int) $d)->unique()->sort()->values()->all();
        $porDia = (int) $data['partidos_por_dia'];
        $hora = $data['hora_defecto']; // "HH:MM"

        $torneo = DB::transaction(function () use ($data, $equipos, $inicio, $dias, $porDia, $hora) {
            $torneo = Torneo::create([
                'nombre' => $data['nombre'],
                'temporada' => $data['temporada'] ?? null,
                'fecha_inicio' => $inicio->toDateString(),
            ]);

            $torneo->equipos()->attach($equipos);

            $programado = $this->programar($this->roundRobin($equipos), $inicio, $dias, $porDia, $hora);

            foreach ($programado as $p) {
                $torneo->partidos()->create([
                    'numero_fecha' => $p['numero_fecha'],
                    'equipo_local_id' => $p['local'],
                    'equipo_visita_id' => $p['visita'],
                    'fecha_hora' => $p['fecha'],
                    'estado' => 'programado',
                ]);
            }

            return $torneo;
        });

        return redirect()->route('partidos.index')
            ->with('status', 'Fixture generado para "'.$torneo->nombre.'": '.count($equipos).' equipos, ida y vuelta.');
    }

    // Reparte cada jornada en su propia semana, sobre los días elegidos,
    // hasta $porDia partidos por día, a la hora por defecto indicada.
    protected function programar(array $jornadas, Carbon $inicio, array $dias, int $porDia, string $hora): array
    {
        [$hh, $mm] = array_map('intval', explode(':', $hora));
        $resultado = [];

        foreach ($jornadas as $r => $enfrentamientos) {
            $iniSemana = $inicio->copy()->addWeeks($r);

            $diasJugables = [];
            for ($d = 0; $d < 7; $d++) {
                $fecha = $iniSemana->copy()->addDays($d);
                if (in_array($fecha->dayOfWeek, $dias, true)) {
                    $diasJugables[] = $fecha;
                }
            }

            $idx = 0;
            $enEsteDia = 0;

            foreach ($enfrentamientos as [$local, $visita]) {
                $fecha = null;

                if (! empty($diasJugables)) {
                    if ($enEsteDia >= $porDia && $idx < count($diasJugables) - 1) {
                        $idx++;
                        $enEsteDia = 0;
                    }
                    // Fecha del día elegido + la hora por defecto.
                    $fecha = $diasJugables[$idx]->copy()->setTime($hh, $mm);
                    $enEsteDia++;
                }

                $resultado[] = [
                    'numero_fecha' => $r + 1,
                    'local' => $local,
                    'visita' => $visita,
                    'fecha' => $fecha,
                ];
            }
        }

        return $resultado;
    }

    protected function roundRobin(array $equipos): array
    {
        $arr = $equipos;

        if (count($arr) % 2 !== 0) {
            $arr[] = null;
        }

        $n = count($arr);
        $rondas = $n - 1;
        $mitad = intdiv($n, 2);

        $ida = [];
        for ($r = 0; $r < $rondas; $r++) {
            $jornada = [];
            for ($i = 0; $i < $mitad; $i++) {
                $local = $arr[$i];
                $visita = $arr[$n - 1 - $i];
                if ($local !== null && $visita !== null) {
                    $jornada[] = [$local, $visita];
                }
            }
            $ida[] = $jornada;
            $arr = $this->rotar($arr);
        }

        $vuelta = [];
        foreach ($ida as $jornada) {
            $inversa = [];
            foreach ($jornada as [$local, $visita]) {
                $inversa[] = [$visita, $local];
            }
            $vuelta[] = $inversa;
        }

        return array_merge($ida, $vuelta);
    }

    protected function rotar(array $arr): array
    {
        $fijo = array_shift($arr);
        $ultimo = array_pop($arr);
        array_unshift($arr, $ultimo);
        array_unshift($arr, $fijo);

        return $arr;
    }
}
