<?php

namespace App\Support;

class Run
{
    /**
     * Normaliza un RUN chileno a "12345678-5" (sin puntos, DV en mayúscula).
     * Devuelve null si el formato o el dígito verificador no son válidos.
     */
    public static function normalizar(?string $run): ?string
    {
        $limpio = strtoupper(preg_replace('/[^0-9kK]/', '', (string) $run));

        if (! preg_match('/^(\d{7,8})([0-9K])$/', $limpio, $m)) {
            return null;
        }

        [, $cuerpo, $dv] = $m;

        return self::digitoVerificador($cuerpo) === $dv ? "{$cuerpo}-{$dv}" : null;
    }

    // Módulo 11: multiplica por 2..7 de derecha a izquierda.
    public static function digitoVerificador(string $cuerpo): string
    {
        $suma = 0;
        $factor = 2;

        for ($i = strlen($cuerpo) - 1; $i >= 0; $i--) {
            $suma += (int) $cuerpo[$i] * $factor;
            $factor = $factor === 7 ? 2 : $factor + 1;
        }

        $resto = 11 - ($suma % 11);

        return match ($resto) {
            11 => '0',
            10 => 'K',
            default => (string) $resto,
        };
    }

    // "12345678-5" → "12.345.678-5"
    public static function formatear(?string $run): string
    {
        if (! $run || ! str_contains($run, '-')) {
            return (string) $run;
        }

        [$cuerpo, $dv] = explode('-', $run, 2);

        return number_format((int) $cuerpo, 0, ',', '.').'-'.$dv;
    }

    // Patente: mayúsculas, sin espacios ni guiones ("bb-cl 12" → "BBCL12").
    public static function normalizarPatente(?string $patente): ?string
    {
        $limpia = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $patente));

        return $limpia === '' ? null : $limpia;
    }
}
