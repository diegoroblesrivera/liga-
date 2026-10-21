<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\Torneo;
use App\Support\LigaActual;
use App\Support\Torneos\Formato;
use App\Support\Torneos\Generador;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FixtureController extends Controller
{
    // Asistente para crear un torneo: etapas, formato y calendario (solo admin).
    public function create(Request $request)
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        return view('fixtures.create', [
            'equipos' => Equipo::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'temporada' => ['nullable', 'string', 'max:20'],
            'etapas' => ['required', Rule::in([1, 2])],
            'tipo' => ['required', Rule::in(['liga', 'grupos', 'eliminacion'])],
            'ida_vuelta' => ['boolean'],
            'grupos' => ['nullable', 'required_if:tipo,grupos', 'integer', 'min:2', 'max:8'],
            'clasifican_grupo' => ['nullable', 'required_if:tipo,grupos', 'integer', Rule::in([1, 2, 4])],
            'clasifican' => ['nullable', 'integer', Rule::in([2, 4, 8, 16])],
            'llave' => ['nullable', 'required_if:tipo,eliminacion', 'integer', Rule::in([8, 16])],
            'equipos' => ['nullable', 'array'],
            'equipos.*' => ['integer', LigaActual::existe('equipos')],
            'tercer_lugar' => ['boolean'],
            'fecha_inicio' => ['required', 'date'],
            'dias_semana' => ['required', 'array', 'min:1'],
            'dias_semana.*' => ['integer', 'between:0,6'],
            'partidos_por_dia' => ['required', 'integer', 'min:1', 'max:50'],
            'hora_defecto' => ['required', 'date_format:H:i'],
        ], [
            'dias_semana.required' => 'Elige al menos un día de la semana.',
            'hora_defecto.date_format' => 'La hora debe tener el formato HH:MM (por ejemplo, 22:00).',
            'llave.required_if' => 'Elige si la llave es de 8 o de 16 equipos.',
        ]);

        [$config, $equipos] = $this->configuracion($data);

        $torneo = Formato::crear($config, $equipos);

        return redirect()->route('partidos.index')->with('status', "Torneo \"{$torneo->nombre}\" creado: ".$this->resumen($config, count($equipos)).'.');
    }

    // Arma la configuración validando que el formato elegido sea posible.
    private function configuracion(array $data): array
    {
        $tipo = $data['tipo'];
        $etapas = (int) $data['etapas'];
        $todos = Equipo::pluck('id')->all();
        $error = fn (string $campo, string $msg) => throw ValidationException::withMessages([$campo => $msg]);

        if ($tipo === 'grupos' && $etapas !== 2) {
            $error('etapas', 'La fase de grupos siempre va seguida de play offs: elige 2 etapas.');
        }
        if ($tipo === 'eliminacion' && $etapas !== 1) {
            $error('etapas', 'La eliminación directa es una sola etapa.');
        }

        $equipos = $todos;
        $etapa1 = ['tipo' => $tipo, 'ida_vuelta' => (bool) ($data['ida_vuelta'] ?? false)];
        $etapa2 = null;
        $tercer = (bool) ($data['tercer_lugar'] ?? false);

        if ($tipo === 'eliminacion') {
            $equipos = array_values(array_unique(array_map('intval', $data['equipos'] ?? [])));
            if (count($equipos) !== (int) $data['llave']) {
                $error('equipos', "Elige exactamente {$data['llave']} equipos para la llave (elegiste ".count($equipos).').');
            }
            $etapa1 += ['clasifican' => (int) $data['llave'], 'tercer_lugar' => $tercer];
        }

        if (count($equipos) < 2) {
            $error('nombre', 'Necesitas al menos 2 equipos creados para armar un torneo.');
        }

        if ($tipo === 'grupos') {
            $g = (int) $data['grupos'];
            $k = (int) $data['clasifican_grupo'];
            $menor = intdiv(count($equipos), $g);
            if ($menor < 2) {
                $error('grupos', "Con ".count($equipos)." equipos no alcanzan para {$g} grupos (mínimo 2 por grupo).");
            }
            if ($k >= $menor) {
                $error('clasifican_grupo', "Si clasifican {$k} por grupo, cada grupo necesita al menos ".($k + 1).' equipos.');
            }
            if (! Generador::esPotenciaDeDos($g * $k) || $g * $k > 16) {
                $error('clasifican_grupo', "{$g} grupos × {$k} clasificados = ".($g * $k).' equipos: para la llave deben ser 2, 4, 8 o 16.');
            }
            $etapa1 += ['grupos' => $g, 'clasifican' => $k];
            $etapa2 = ['clasifican' => $g * $k, 'tercer_lugar' => $tercer];
        }

        if ($tipo === 'liga' && $etapas === 2) {
            $n = (int) ($data['clasifican'] ?? 0);
            if (! $n) {
                $error('clasifican', 'Elige cuántos equipos pasan a la eliminación directa.');
            }
            if ($n > count($equipos)) {
                $error('clasifican', "No pueden clasificar {$n} si el torneo tiene ".count($equipos).' equipos.');
            }
            $etapa2 = ['clasifican' => $n, 'tercer_lugar' => $tercer];
        }

        return [[
            'nombre' => $data['nombre'],
            'temporada' => $data['temporada'] ?? null,
            'inicio' => $data['fecha_inicio'],
            'etapa1' => $etapa1,
            'etapa2' => $etapa2,
            'programacion' => [
                'dias' => collect($data['dias_semana'])->map(fn ($d) => (int) $d)->unique()->sort()->values()->all(),
                'por_dia' => (int) $data['partidos_por_dia'],
                'hora' => $data['hora_defecto'],
            ],
        ], $equipos];
    }

    private function resumen(array $c, int $equipos): string
    {
        $e1 = $c['etapa1'];
        $texto = match ($e1['tipo']) {
            'liga' => "{$equipos} equipos, todos contra todos".($e1['ida_vuelta'] ? ' ida y vuelta' : ''),
            'grupos' => "{$equipos} equipos en {$e1['grupos']} grupos",
            'eliminacion' => "eliminación directa de {$equipos} equipos",
        };

        return $c['etapa2'] ? "{$texto}; luego eliminación directa de {$c['etapa2']['clasifican']}" : $texto;
    }

    // Genera la eliminatoria (2ª etapa) cuando terminó la 1ª.
    public function segundaEtapa(Request $request, Torneo $torneo)
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        if (! Formato::segundaPendiente($torneo)) {
            return back()->withErrors(['torneo' => 'La primera etapa aún tiene partidos por jugar.']);
        }

        $etapa = Formato::generarSegunda($torneo);

        return redirect()->route('tablas.posiciones', ['torneo' => $torneo->id])
            ->with('status', "Eliminatoria generada: {$etapa->clasifican} equipos clasificados.");
    }
}
