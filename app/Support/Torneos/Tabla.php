<?php

namespace App\Support\Torneos;

use App\Models\Etapa;
use App\Models\Torneo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

// Tabla de posiciones calculada desde los partidos finalizados (3 pts victoria, 1 empate).
// Orden: puntos, diferencia de gol, goles a favor y nombre. Los partidos de eliminación
// directa no suman: solo cuentan liga y grupos.
class Tabla
{
    /**
     * Tabla general del torneo (torneos sin etapas o con etapa de liga).
     *
     * @return Collection<int, object> filas con equipo_id, equipo, pj, pg, pe, pp, gf, gc, dif, pts
     */
    public static function delTorneo(Torneo $torneo): Collection
    {
        $partidos = DB::table('partidos')->where('torneo_id', $torneo->id)->whereNull('ronda')->where('estado', 'finalizado')->get();

        return self::calcular($partidos, DB::table('equipo_torneo')->where('torneo_id', $torneo->id)->pluck('equipo_id'));
    }

    /** @return Collection<string, Collection> grupo => filas */
    public static function porGrupo(Etapa $etapa): Collection
    {
        $miembros = DB::table('etapa_equipo')->where('etapa_id', $etapa->id)->get()->groupBy('grupo');
        $partidos = DB::table('partidos')->where('etapa_id', $etapa->id)->where('estado', 'finalizado')->get()->groupBy('grupo');

        return $miembros->sortKeys()->map(fn ($equipos, $grupo) => self::calcular($partidos->get($grupo, collect()), $equipos->pluck('equipo_id')));
    }

    public static function deEtapa(Etapa $etapa): Collection
    {
        $partidos = DB::table('partidos')->where('etapa_id', $etapa->id)->where('estado', 'finalizado')->get();

        return self::calcular($partidos, DB::table('etapa_equipo')->where('etapa_id', $etapa->id)->pluck('equipo_id'));
    }

    // $equipos: todos los participantes (aparecen con 0 aunque no hayan jugado).
    public static function calcular(Collection $partidos, Collection $equipos): Collection
    {
        $nombres = DB::table('equipos')->whereIn('id', $equipos->merge($partidos->pluck('equipo_local_id'))->merge($partidos->pluck('equipo_visita_id'))->unique())
            ->pluck('nombre', 'id');

        $filas = $nombres->map(fn ($nombre, $id) => (object) [
            'equipo_id' => (int) $id, 'equipo' => $nombre,
            'pj' => 0, 'pg' => 0, 'pe' => 0, 'pp' => 0, 'gf' => 0, 'gc' => 0, 'dif' => 0, 'pts' => 0,
        ]);

        foreach ($partidos as $p) {
            if ($p->goles_local === null || $p->goles_visita === null) {
                continue;
            }
            foreach ([[$p->equipo_local_id, (int) $p->goles_local, (int) $p->goles_visita], [$p->equipo_visita_id, (int) $p->goles_visita, (int) $p->goles_local]] as [$id, $gf, $gc]) {
                $f = $filas[$id];
                $f->pj++;
                $f->gf += $gf;
                $f->gc += $gc;
                $f->dif = $f->gf - $f->gc;
                if ($gf > $gc) {
                    $f->pg++;
                    $f->pts += 3;
                } elseif ($gf === $gc) {
                    $f->pe++;
                    $f->pts++;
                } else {
                    $f->pp++;
                }
            }
        }

        return $filas->sortBy([['pts', 'desc'], ['dif', 'desc'], ['gf', 'desc'], ['equipo', 'asc']])->values();
    }
}
