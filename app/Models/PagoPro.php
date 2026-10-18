<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Un pago (o activación manual) del plan Pro de un campeonato.
class PagoPro extends Model
{
    protected $table = 'pagos_pro';

    protected $fillable = [
        'liga_id', 'user_id', 'medio', 'estado', 'monto', 'mp_preferencia_id', 'mp_pago_id',
        'pagado_en', 'pro_desde', 'pro_hasta', 'nota',
    ];

    protected $casts = [
        'monto' => 'integer',
        'pagado_en' => 'datetime',
        'pro_desde' => 'datetime',
        'pro_hasta' => 'datetime',
    ];

    public function liga(): BelongsTo
    {
        return $this->belongsTo(Liga::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
