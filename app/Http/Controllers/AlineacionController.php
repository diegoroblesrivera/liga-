<?php

namespace App\Http\Controllers;

use App\Models\Equipo;

class AlineacionController extends Controller
{
    public function equipo(Equipo $equipo)
    {
        $this->authorize('view', $equipo);

        $equipo->load('modalidad', 'jugadores');

        return view('equipos.alineacion', array_merge(
            ['equipo' => $equipo],
            $this->armarFormacion($equipo)
        ));
    }

    protected function armarFormacion(Equipo $equipo): array
    {
        $enCancha = $this->enCancha($equipo);
        $config = $this->lineasPara($enCancha);

        $jugadores = $equipo->jugadores
            ->sortBy(fn ($j) => $j->dorsal ?? 999)
            ->values();

        $arquero = $jugadores->first(function ($j) {
            return preg_match('/arquer|porter|goler|arco/i', (string) $j->posicion);
        });

        $resto = $jugadores
            ->reject(fn ($j) => $arquero && $j->id === $arquero->id)
            ->values();

        $lineas = [];
        $i = 0;
        foreach ($config as $cantidad) {
            $linea = [];
            for ($k = 0; $k < $cantidad; $k++) {
                $linea[] = $resto[$i] ?? null;
                $i++;
            }
            $lineas[] = $linea;
        }

        return ['arquero' => $arquero, 'lineas' => $lineas];
    }

    /**
     * Total de jugadores en cancha (arquero incluido) según la modalidad.
     * Usa la columna jugadores_en_cancha; si no está poblada, la deduce.
     */
    protected function enCancha(Equipo $equipo): int
    {
        $val = $equipo->modalidad->jugadores_en_cancha ?? null;
        if ($val) {
            return (int) $val;
        }

        $nombre = mb_strtolower($equipo->modalidad->nombre ?? '');
        if (str_contains($nombre, 'baby')) {
            return 5;
        }
        if (str_contains($nombre, '11') || str_contains($nombre, 'once')) {
            return 11;
        }
        if (str_contains($nombre, 'futbolito')) {
            return 7;
        }

        $max = (int) ($equipo->modalidad->max_jugadores ?? 10);

        return match ($max) {
            8 => 5,
            18 => 11,
            default => 7,
        };
    }

    /**
     * Convierte el total en cancha en líneas de campo (sin el arquero).
     * Casos conocidos: 5→2-2, 7→2-2-2, 11→4-4-2. El resto se reparte solo.
     */
    protected function lineasPara(int $enCancha): array
    {
        $campo = max(0, $enCancha - 1); // se descuenta el arquero

        return match ($campo) {
            4 => [2, 2],
            6 => [2, 2, 2],
            10 => [4, 4, 2],
            default => $this->repartir($campo),
        };
    }

    protected function repartir(int $n): array
    {
        if ($n <= 0) {
            return [];
        }
        if ($n <= 3) {
            return [$n];
        }
        if ($n <= 5) {
            $ataque = intdiv($n, 2);
            return [$n - $ataque, $ataque];
        }

        $delanteros = max(1, intdiv($n, 4));
        $resto = $n - $delanteros;
        $defensas = (int) ceil($resto / 2);
        $medios = $resto - $defensas;

        return [$defensas, $medios, $delanteros];
    }
}
