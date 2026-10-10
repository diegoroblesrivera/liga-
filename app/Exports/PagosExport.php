<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PagosExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function __construct(protected array $filas)
    {
    }

    public function array(): array
    {
        return $this->filas;
    }

    public function headings(): array
    {
        return ['Jornada', 'Fecha y hora', 'Partido', 'Equipo', 'Monto', 'Estado', 'Fecha de subida'];
    }
}
