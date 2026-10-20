<?php

namespace App\Support;

use App\Models\Equipo;
use App\Models\Gol;
use App\Models\Liga;
use App\Models\Partido;
use App\Models\Torneo;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

// Caché de las páginas públicas (portada y página pública del campeonato): son iguales
// para todos los visitantes. Cada campeonato tiene una "versión" que sube cuando cambia
// un partido, gol, equipo o torneo, así un resultado nuevo se ve al instante.
class CachePublico
{
    private const TTL = 600; // respaldo: aunque nada cambie, se recalcula cada 10 minutos

    public static function liga(int $ligaId, string $clave, Closure $calcular): mixed
    {
        return Cache::remember("publico:liga:{$ligaId}:v".self::version($ligaId).":{$clave}", self::TTL, $calcular);
    }

    public static function portada(Closure $calcular): mixed
    {
        return Cache::remember('publico:portada:v'.self::version(0), self::TTL, $calcular);
    }

    public static function invalidar(?int $ligaId): void
    {
        if ($ligaId) {
            Cache::forever("publico:version:{$ligaId}", self::version($ligaId) + 1);
        }
        // La portada muestra conteos de equipos y torneos de todos los campeonatos.
        Cache::forever('publico:version:0', self::version(0) + 1);
    }

    private static function version(int $ligaId): int
    {
        return (int) Cache::get("publico:version:{$ligaId}", 1);
    }

    public static function registrar(): void
    {
        $porTorneo = fn ($torneoId) => DB::table('torneos')->where('id', $torneoId)->value('liga_id');
        $porPartido = fn ($partidoId) => $porTorneo(DB::table('partidos')->where('id', $partidoId)->value('torneo_id'));

        $eventos = [
            Liga::class => fn (Liga $l) => $l->id,
            Torneo::class => fn (Torneo $t) => $t->liga_id,
            Equipo::class => fn (Equipo $e) => $e->liga_id,
            Partido::class => fn (Partido $p) => $porTorneo($p->torneo_id),
            Gol::class => fn (Gol $g) => $porPartido($g->partido_id),
        ];

        foreach ($eventos as $modelo => $ligaDe) {
            // Después del commit: si no, una visita intermedia guardaría los datos viejos.
            $alCambiar = fn ($registro) => DB::afterCommit(fn () => self::invalidar($ligaDe($registro)));
            $modelo::saved($alCambiar);
            $modelo::deleted($alCambiar);
        }
    }
}
