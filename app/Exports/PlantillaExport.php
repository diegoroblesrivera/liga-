<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

// Plantilla de carga masiva: títulos y una fila de ejemplo.
class PlantillaExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function __construct(protected array $titulos, protected array $ejemplos)
    {
    }

    public function array(): array
    {
        return $this->ejemplos;
    }

    public function headings(): array
    {
        return $this->titulos;
    }
}
