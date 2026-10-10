<?php

namespace App\Support;

use App\Models\Partido;
use App\Models\Tarjeta;

class Suspension
{
    /**
     * IDs de jugadores del equipo que están suspendidos para ESTE partido,
     * por lo ocurrido en el partido inmediatamente anterior del equipo:
     * roja directa o dos amarillas en ese partido.
     */
    public static function suspendidosEnPartido(Partido $partido, int $equipoId): array
    {
        $anterior = self::partidoAnterior($partido, $equipoId);
        if (! $anterior) {
            return [];
        }

        $porJugador = Tarjeta::where('partido_id', $anterior->id)
            ->where('equipo_id', $equipoId)
            ->get()
            ->groupBy('jugador_id');

        $ids = [];
        foreach ($porJugador as $jugadorId => $tarjetas) {
            $rojas = $tarjetas->where('tipo', 'roja')->count();
            $amarillas = $tarjetas->where('tipo', 'amarilla')->count();

            if ($rojas >= 1 || $amarillas >= 2) {
                $ids[] = (int) $jugadorId;
            }
        }

        return $ids;
    }

    /**
     * Partido inmediatamente anterior del equipo dentro del mismo torneo.
     */
    protected static function partidoAnterior(Partido $partido, int $equipoId): ?Partido
    {
        return Partido::where('torneo_id', $partido->torneo_id)
            ->where('numero_fecha', '<', $partido->numero_fecha)
            ->where(function ($q) use ($equipoId) {
                $q->where('equipo_local_id', $equipoId)
                    ->orWhere('equipo_visita_id', $equipoId);
            })
            ->orderByDesc('numero_fecha')
            ->orderByDesc('fecha_hora')
            ->first();
    }
}
