<?php

namespace App\Support;

use App\Models\Liga;
use Closure;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

/**
 * Campeonato (liga) en el que se está trabajando en este request.
 * Lo fija el middleware ResolverLigaActual a partir de la sesión del usuario.
 * Los modelos del campeonato se filtran solos por esta liga (ver PerteneceALiga).
 * Sin liga fijada (invitados, consola) no se filtra nada.
 */
class LigaActual
{
    protected static ?Liga $liga = null;

    public static function liga(): ?Liga
    {
        return self::$liga;
    }

    public static function id(): ?int
    {
        return self::$liga?->id;
    }

    public static function fijar(?Liga $liga): void
    {
        self::$liga = $liga;
    }

    // Validación "exists" limitada al campeonato actual. Las reglas de validación no pasan
    // por el filtro de los modelos: sin esto se podría enviar el ID de un equipo ajeno.
    public static function existe(string $tabla): Exists
    {
        return Rule::exists($tabla, 'id')->where('liga_id', self::id());
    }

    // El usuario debe ser árbitro de este campeonato.
    public static function existeArbitro(): Exists
    {
        return Rule::exists('liga_user', 'user_id')->where('liga_id', self::id())->where('rol', 'arbitro');
    }

    // Nombre único dentro del campeonato (p. ej. dos ligas pueden tener un "Los Cracks").
    public static function unico(string $tabla, string $columna, ?int $ignorarId = null): Unique
    {
        return Rule::unique($tabla, $columna)->where('liga_id', self::id())->ignore($ignorarId);
    }

    // Ejecuta $callback con otra liga como actual y luego restaura la anterior.
    // Para páginas públicas o invitaciones de un campeonato distinto al del usuario.
    public static function usar(Liga $liga, Closure $callback): mixed
    {
        $anterior = self::$liga;
        self::$liga = $liga;

        try {
            return $callback();
        } finally {
            self::$liga = $anterior;
        }
    }
}
