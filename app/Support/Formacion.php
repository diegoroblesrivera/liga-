<?php

namespace App\Support;

use App\Models\Convocatoria;
use App\Models\Equipo;
use App\Models\Partido;

class Formacion
{
    /**
     * Devuelve la formación de un equipo para un partido, a partir de sus
     * titulares convocados: ['arquero'=>Jugador|null, 'lineas'=>[...], 'hayConvocatoria'=>bool].
     */
    public static function delPartido(Partido $partido, Equipo $equipo): array
    {
        $equipo->loadMissing('modalidad');

        $convs = Convocatoria::where('partido_id', $partido->id)
            ->where('es_titular', true)
            ->whereIn('jugador_id', $equipo->jugadores()->pluck('id'))
            ->with('jugador')
            ->get();

        $arquero = $convs->firstWhere('es_arquero', true)?->jugador;

        $resto = $convs
            ->reject(fn ($c) => $c->es_arquero)
            ->map(fn ($c) => $c->jugador)
            ->values();

        $config = self::lineasPara(self::enCancha($equipo));

        $lineas = [];
        $i = 0;
        foreach ($config as $cant) {
            $linea = [];
            for ($k = 0; $k < $cant; $k++) {
                $linea[] = $resto[$i] ?? null;
                $i++;
            }
            $lineas[] = $linea;
        }

        return [
            'arquero' => $arquero,
            'lineas' => $lineas,
            'hayConvocatoria' => $convs->isNotEmpty(),
        ];
    }

    public static function enCancha(Equipo $equipo): int
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

    public static function lineasPara(int $enCancha): array
    {
        $campo = max(0, $enCancha - 1);

        return match ($campo) {
            4 => [2, 2],
            6 => [2, 2, 2],
            10 => [4, 4, 2],
            default => self::repartir($campo),
        };
    }

    protected static function repartir(int $n): array
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
