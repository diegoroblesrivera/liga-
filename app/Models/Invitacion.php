<?php

namespace App\Models;

use App\Models\Concerns\PerteneceALiga;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invitacion extends Model
{
    use PerteneceALiga;

    protected $table = 'invitaciones';

    protected $fillable = [
        'equipo_id', 'jugador_id', 'tipo', 'token', 'email', 'nombre', 'telefono',
        'reutilizable', 'usos', 'expira_en', 'enviada_en', 'creada_por',
    ];

    protected $casts = [
        'reutilizable' => 'boolean',
        'usos' => 'integer',
        'expira_en' => 'datetime',
        'enviada_en' => 'datetime',
    ];

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    // Ficha ya cargada (carga masiva) que el jugador reclama al aceptar.
    public function jugador(): BelongsTo
    {
        return $this->belongsTo(Jugador::class)->withoutGlobalScopes();
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
