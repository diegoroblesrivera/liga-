<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invitacion extends Model
{
    protected $table = 'invitaciones';

    protected $fillable = [
        'equipo_id', 'tipo', 'token', 'email',
        'reutilizable', 'usos', 'expira_en', 'creada_por',
    ];

    protected $casts = [
        'reutilizable' => 'boolean',
        'usos' => 'integer',
        'expira_en' => 'datetime',
    ];

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creada_por');
    }

    // ¿La invitación sigue siendo utilizable?
    public function esValida(): bool
    {
        if ($this->expira_en && $this->expira_en->isPast()) {
            return false;
        }

        // Las de tipo 'dt' (reutilizable = false) son de un solo uso
        if (! $this->reutilizable && $this->usos > 0) {
            return false;
        }

        return true;
    }
}
