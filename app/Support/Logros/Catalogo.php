<?php

namespace App\Support\Logros;

// Catálogo de logros. "ambito" dice quién lo gana y cómo se recalcula:
//   jugador  → ficha del jugador, con su historia de partidos en el campeonato
//   torneo   → ficha del jugador, al cerrar una fecha o el torneo
//   pichanga → el usuario, con sus pichangas jugadas
// "pro" = solo se gana en campeonatos Pro (el que ya se ganó no se pierde).
// "meta" = número para mostrar el progreso (7/10 goles).
class Catalogo
{
    public const LOGROS = [
        // --- Para todos ---
        'debut' => ['nombre' => 'Debut', 'icono' => '👟', 'descripcion' => 'Jugaste tu primer partido del campeonato', 'ambito' => 'jugador', 'pro' => false, 'nivel' => 'bronce'],
        'primer_gol' => ['nombre' => 'Primer gol', 'icono' => '⚽', 'descripcion' => 'Marcaste tu primer gol', 'ambito' => 'jugador', 'pro' => false, 'nivel' => 'bronce', 'meta' => 1, 'medida' => 'goles'],
        'goleador_10' => ['nombre' => 'Goleador', 'icono' => '🎯', 'descripcion' => '10 goles en el campeonato', 'ambito' => 'jugador', 'pro' => false, 'nivel' => 'bronce', 'meta' => 10, 'medida' => 'goles'],
        'goleador_25' => ['nombre' => 'Goleador de plata', 'icono' => '🎯', 'descripcion' => '25 goles en el campeonato', 'ambito' => 'jugador', 'pro' => false, 'nivel' => 'plata', 'meta' => 25, 'medida' => 'goles'],
        'goleador_50' => ['nombre' => 'Goleador de oro', 'icono' => '🎯', 'descripcion' => '50 goles en el campeonato', 'ambito' => 'jugador', 'pro' => false, 'nivel' => 'oro', 'meta' => 50, 'medida' => 'goles'],
        'hat_trick' => ['nombre' => 'Hat-trick', 'icono' => '🎩', 'descripcion' => '3 goles en un mismo partido', 'ambito' => 'jugador', 'pro' => false, 'nivel' => 'plata'],
        'partidos_10' => ['nombre' => '10 partidos', 'icono' => '🏃', 'descripcion' => 'Jugaste 10 partidos', 'ambito' => 'jugador', 'pro' => false, 'nivel' => 'bronce', 'meta' => 10, 'medida' => 'partidos'],
        'partidos_50' => ['nombre' => '50 partidos', 'icono' => '🏃', 'descripcion' => 'Jugaste 50 partidos', 'ambito' => 'jugador', 'pro' => false, 'nivel' => 'plata', 'meta' => 50, 'medida' => 'partidos'],
        'partidos_100' => ['nombre' => '100 partidos', 'icono' => '🏃', 'descripcion' => 'Jugaste 100 partidos', 'ambito' => 'jugador', 'pro' => false, 'nivel' => 'oro', 'meta' => 100, 'medida' => 'partidos'],
        'valla_invicta' => ['nombre' => 'Valla invicta', 'icono' => '🧤', 'descripcion' => 'De arquero, terminaste un partido sin goles en contra', 'ambito' => 'jugador', 'pro' => false, 'nivel' => 'plata'],
        'fair_play' => ['nombre' => 'Fair play', 'icono' => '😇', 'descripcion' => '10 partidos seguidos sin tarjetas', 'ambito' => 'jugador', 'pro' => false, 'nivel' => 'plata', 'meta' => 10, 'medida' => 'partidos sin tarjeta'],
        'pichanguero_10' => ['nombre' => 'Pichanguero', 'icono' => '🔥', 'descripcion' => 'Jugaste 10 pichangas', 'ambito' => 'pichanga', 'pro' => false, 'nivel' => 'bronce', 'meta' => 10, 'medida' => 'pichangas'],
        'pichanguero_50' => ['nombre' => 'Pichanguero de oro', 'icono' => '🔥', 'descripcion' => 'Jugaste 50 pichangas', 'ambito' => 'pichanga', 'pro' => false, 'nivel' => 'oro', 'meta' => 50, 'medida' => 'pichangas'],
        'pichanga_hat_trick' => ['nombre' => 'Crack de pichanga', 'icono' => '🎩', 'descripcion' => '3 goles en una pichanga', 'ambito' => 'pichanga', 'pro' => false, 'nivel' => 'plata'],

        // --- Solo en campeonatos Pro ---
        'racha_goleadora' => ['nombre' => 'Racha goleadora', 'icono' => '⚡', 'descripcion' => 'Goles en 5 partidos seguidos', 'ambito' => 'jugador', 'pro' => true, 'nivel' => 'oro', 'meta' => 5, 'medida' => 'partidos seguidos con gol'],
        'campeon' => ['nombre' => 'Campeón', 'icono' => '🏆', 'descripcion' => 'Ganaste un torneo', 'ambito' => 'torneo', 'pro' => true, 'nivel' => 'oro'],
        'pichichi' => ['nombre' => 'Pichichi', 'icono' => '👑', 'descripcion' => 'Goleador de un torneo', 'ambito' => 'torneo', 'pro' => true, 'nivel' => 'oro'],
        'asistencia_perfecta' => ['nombre' => 'Asistencia perfecta', 'icono' => '✅', 'descripcion' => 'En la lista de todos los partidos de tu equipo en un torneo', 'ambito' => 'torneo', 'pro' => true, 'nivel' => 'plata'],
        'figura_fecha' => ['nombre' => 'Figura de la fecha', 'icono' => '🦸', 'descripcion' => 'El que más goles hizo en una fecha (mínimo 2)', 'ambito' => 'torneo', 'pro' => true, 'nivel' => 'plata'],
    ];

    public static function get(string $clave): array
    {
        return ['clave' => $clave] + (self::LOGROS[$clave] ?? ['nombre' => $clave, 'icono' => '🏅', 'descripcion' => '', 'ambito' => 'jugador', 'pro' => false, 'nivel' => 'bronce']);
    }

    /** @return array<string> */
    public static function clavesDe(string $ambito): array
    {
        return array_keys(array_filter(self::LOGROS, fn ($l) => $l['ambito'] === $ambito));
    }

    public static function esPro(string $clave): bool
    {
        return (bool) (self::LOGROS[$clave]['pro'] ?? false);
    }
}
