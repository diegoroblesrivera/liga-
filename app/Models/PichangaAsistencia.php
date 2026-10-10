<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PichangaAsistencia extends Model
{
    protected $table = 'pichanga_asistencias';

    protected $fillable = [
        'pichanga_fecha_id', 'user_id', 'estado', 'anotado_en', 'equipo', 'pagado', 'goles',
        'comprobante_path', 'comprobante_subido_en',
    ];

    protected $casts = [
        'anotado_en' => 'datetime',
        'comprobante_subido_en' => 'datetime',
        'pagado' => 'boolean',
        'goles' => 'integer',
    ];

    public function fecha(): BelongsTo
    {
        return $this->belongsTo(PichangaFecha::class, 'pichanga_fecha_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
