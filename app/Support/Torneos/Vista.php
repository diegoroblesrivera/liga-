<?php

namespace App\Support\Torneos;

use App\Models\Partido;
use App\Models\Torneo;
use Illuminate\Support\Collection;

// Lo que se muestra en "Posiciones" (y en la página pública) según el formato del torneo:
// tabla general, tablas por grupo y/o la llave de eliminación directa.
class Vista
{
    /** @return array<int, array> secciones en orden */
    public static function secciones(Torneo $torneo): array
    {
        $etapas = $torneo->etapas()->get();

        // Torneos anteriores a los formatos: una sola tabla.
        if ($etapas->isEmpty()) {
            return [['tipo' => 'tabla', 'titulo' => null, 'filas' => Tabla::delTorneo($torneo), 'clasifican' => 0]];
        }

        $siguiente = $etapas->firstWhere('orden', 2);
        $secciones = [];

        foreach ($etapas as $etapa) {
            $titulo = $etapas->count() > 1 ? "Etapa {$etapa->orden} · {$etapa->nombre()}" : null;

            if ($etapa->tipo === 'liga') {
                $secciones[] = ['tipo' => 'tabla', 'titulo' => $titulo, 'filas' => Tabla::deEtapa($etapa), 'clasifican' => $siguiente?->clasifican ?? 0];
            } elseif ($etapa->tipo === 'grupos') {
                $secciones[] = ['tipo' => 'grupos', 'titulo' => $titulo, 'grupos' => Tabla::porGrupo($etapa), 'clasifican' => $etapa->clasifican];
            } else {
                $secciones[] = ['tipo' => 'llave', 'titulo' => $titulo, 'etapa' => $etapa] + self::llave($etapa->partidos()->with('equipoLocal', 'equipoVisita')->get(), $etapa->clasifican);
            }
        }

        return $secciones;
    }

    // Rondas de la llave en orden, con las futuras como "por definir".
    private static function llave(Collection $partidos, ?int $equipos): array
    {
        $rondas = [];
        $n = $equipos ?: max(2, $partidos->whereNotIn('ronda', ['tercer_lugar'])->groupBy('ronda')->max(fn ($r) => $r->count()) * 2);

        while ($n >= 2) {
            $ronda = Generador::ronda($n);
            $jugados = $partidos->where('ronda', $ronda)->sortBy('llave')->values();
            $rondas[] = [
                'nombre' => Partido::RONDAS[$ronda],
                'partidos' => collect(range(1, intdiv($n, 2)))->map(fn ($llave) => $jugados->firstWhere('llave', $llave)),
            ];
            $n = intdiv($n, 2);
        }

        return ['rondas' => $rondas, 'tercer' => $partidos->firstWhere('ronda', 'tercer_lugar'), 'pendiente' => $partidos->isEmpty()];
    }
}
