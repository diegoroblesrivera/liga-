<?php

namespace App\Support\Torneos;

use Illuminate\Support\Carbon;

// Armado de enfrentamientos y calendario (sin tocar la base de datos).
class Generador
{
    // Todos contra todos (método del círculo). Devuelve jornadas: [[local, visita], ...].
    public static function roundRobin(array $equipos, bool $idaVuelta): array
    {
        $arr = array_values($equipos);
        if (count($arr) % 2 !== 0) {
            $arr[] = null; // libre
        }

        $n = count($arr);
        $ida = [];
        for ($r = 0; $r < $n - 1; $r++) {
            $jornada = [];
            for ($i = 0; $i < intdiv($n, 2); $i++) {
                [$local, $visita] = [$arr[$i], $arr[$n - 1 - $i]];
                if ($local !== null && $visita !== null) {
                    // Alterna la localía para que nadie juegue siempre de local.
                    $jornada[] = $r % 2 === 0 ? [$local, $visita] : [$visita, $local];
                }
            }
            $ida[] = $jornada;
            $fijo = array_shift($arr);
            array_unshift($arr, array_pop($arr));
            array_unshift($arr, $fijo);
        }

        if (! $idaVuelta) {
            return $ida;
        }

        return array_merge($ida, array_map(fn ($j) => array_map(fn ($p) => [$p[1], $p[0]], $j), $ida));
    }

    // Reparte los equipos (ya sorteados) en grupos A, B, C... de tamaños parejos.
    /** @return array<string, array<int>> */
    public static function grupos(array $equipos, int $cantidad): array
    {
        $grupos = [];
        foreach (array_values($equipos) as $i => $equipo) {
            $grupos[chr(65 + $i % $cantidad)][] = $equipo;
        }
        ksort($grupos);

        return $grupos;
    }

    // Jornadas de la fase de grupos: la fecha k junta la jornada k de cada grupo.
    // Cada enfrentamiento: [local, visita, grupo].
    public static function jornadasDeGrupos(array $grupos, bool $idaVuelta): array
    {
        $jornadas = [];
        foreach ($grupos as $letra => $equipos) {
            foreach (self::roundRobin($equipos, $idaVuelta) as $k => $jornada) {
                foreach ($jornada as [$local, $visita]) {
                    $jornadas[$k][] = [$local, $visita, $letra];
                }
            }
        }
        ksort($jornadas);

        return array_values($jornadas);
    }

    // Orden de siembra para una llave de n equipos: 1 y 2 solo se cruzan en la final.
    // n=8 → [1, 8, 4, 5, 2, 7, 3, 6] (cruces 1-8, 4-5, 2-7, 3-6).
    public static function siembra(int $n): array
    {
        $orden = [1];
        while (count($orden) < $n) {
            $total = count($orden) * 2 + 1;
            $orden = array_merge(...array_map(fn ($s) => [$s, $total - $s], $orden));
        }

        return $orden;
    }

    // Nombre de la ronda según cuántos equipos quedan.
    public static function ronda(int $equipos): string
    {
        return match ($equipos) {
            2 => 'final',
            4 => 'semifinal',
            8 => 'cuartos',
            16 => 'octavos',
            default => 'dieciseisavos',
        };
    }

    public static function esPotenciaDeDos(int $n): bool
    {
        return $n >= 2 && ($n & ($n - 1)) === 0;
    }

    /**
     * Calendario: cada jornada en su semana (desde $inicio), en los días elegidos,
     * hasta $porDia partidos por día y a la hora indicada.
     *
     * @param  array  $jornadas  lista de jornadas; cada partido es un array que se devuelve con 'fecha'
     * @return array<int, array{jornada: int, partido: array, fecha: ?Carbon}>
     */
    public static function programar(array $jornadas, Carbon $inicio, array $programacion): array
    {
        [$hh, $mm] = array_map('intval', explode(':', $programacion['hora'] ?? '22:00'));
        $dias = $programacion['dias'] ?? [];
        $porDia = max(1, (int) ($programacion['por_dia'] ?? 1));
        $resultado = [];

        foreach (array_values($jornadas) as $r => $partidos) {
            $semana = $inicio->copy()->addWeeks($r);
            $jugables = [];
            for ($d = 0; $d < 7; $d++) {
                $dia = $semana->copy()->addDays($d);
                if (in_array($dia->dayOfWeek, $dias, true)) {
                    $jugables[] = $dia;
                }
            }

            $idx = 0;
            $enDia = 0;
            foreach ($partidos as $partido) {
                $fecha = null;
                if ($jugables) {
                    if ($enDia >= $porDia && $idx < count($jugables) - 1) {
                        $idx++;
                        $enDia = 0;
                    }
                    $fecha = $jugables[$idx]->copy()->setTime($hh, $mm);
                    $enDia++;
                }
                $resultado[] = ['jornada' => $r, 'partido' => $partido, 'fecha' => $fecha];
            }
        }

        return $resultado;
    }
}
