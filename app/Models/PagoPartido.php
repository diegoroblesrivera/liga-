<?php

namespace App\Models;

use App\Models\Concerns\FiltradoPorLigaVia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoPartido extends Model
{
    use FiltradoPorLigaVia;

    // Pertenece al campeonato a través de su partido.
    protected static array $ligaVia = ['partido_id', Partido::class];

    protected $table = 'pagos_partidos';

    protected $fillable = [
        'partido_id', 'equipo_id', 'comprobante_path', 'subido_por', 'fecha_subida',
    ];

    protected $casts = [
        'fecha_subida' => 'datetime',
    ];

    public function partido(): BelongsTo
    {
        return $this->belongsTo(Partido::class);
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function subidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subido_por');
    }
}
