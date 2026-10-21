<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

// Lee la primera hoja de un Excel/CSV de carga masiva. La primera fila son los títulos;
// se reconocen por nombre (sin importar mayúsculas, tildes ni el orden de las columnas).
class Planilla
{
    /**
     * @param  array<string, array<string>>  $columnas  campo => títulos aceptados
     * @return array{filas: array<int, array<string, ?string>>, faltan: array<string>}  filas indexadas por número de fila del Excel
     */
    public static function leer(UploadedFile $archivo, array $columnas, array $obligatorias): array
    {
        try {
            $hoja = Excel::toArray(new class implements Import {}, $archivo)[0] ?? [];
        } catch (Throwable $e) {
            report($e);

            return ['filas' => [], 'faltan' => [], 'ilegible' => true];
        }

        $titulos = array_map(fn ($t) => self::normalizar((string) $t), array_shift($hoja) ?? []);

        $posicion = [];
        foreach ($columnas as $campo => $aceptados) {
            foreach ($titulos as $i => $titulo) {
                if (in_array($titulo, array_map([self::class, 'normalizar'], $aceptados), true)) {
                    $posicion[$campo] = $i;
                    break;
                }
            }
        }

        $faltan = array_values(array_diff($obligatorias, array_keys($posicion)));

        $filas = [];
        foreach ($hoja as $i => $fila) {
            // Todos los campos presentes (null si el archivo no trae esa columna opcional).
            $datos = array_fill_keys(array_keys($columnas), null);
            foreach ($posicion as $campo => $col) {
                $valor = trim((string) ($fila[$col] ?? ''));
                $datos[$campo] = $valor === '' ? null : $valor;
            }
            if (array_filter($datos, fn ($v) => $v !== null)) { // ignora filas vacías
                $filas[$i + 2] = $datos; // +2: los títulos son la fila 1
            }
        }

        return ['filas' => $filas, 'faltan' => $faltan, 'ilegible' => false];
    }

    private static function normalizar(string $texto): string
    {
        $texto = preg_replace('/\(.*?\)/', '', $texto); // "Teléfono (opcional)" → "Teléfono"

        return Str::of($texto)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->trim()->value();
    }
}
