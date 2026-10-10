<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PichangaAsistencia extends Model
{
    protected $table = 'pichanga_asistencias';

    protected $fillable = [
        'pichanga_fecha_id', 'user_id', 'estado', 'anotado_en', 'equipo', 'pagado', 'goles',
        'comprobante_path', 'comprobante_subido_en', 'pagado_por',
        'invitado_por', 'invitado_nombre', 'invitado_run',
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

    // Jugador con cuenta (null si es un invitado).
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Quién lo trajo, si es un invitado sin cuenta.
    public function invitadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invitado_por');
    }

    // Quién pagó por él, si no pagó él mismo.
    public function pagadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pagado_por');
    }

    public function esInvitado(): bool
    {
        return $this->invitado_por !== null;
    }

    // "DiegoR", "Juan Pérez (invitado de DiegoR)" o "Jugador nuevo (DiegoR)".
    public function nombreVisible(): string
    {
        if (! $this->esInvitado()) {
            return $this->user?->name ?? 'Jugador';
        }

        $anfitrion = $this->invitadoPor?->name ?? '?';

        return $this->invitado_nombre
            ? "{$this->invitado_nombre} (invitado de {$anfitrion})"
            : "Jugador nuevo ({$anfitrion})";
    }

    // RUN para la lista de acceso: el del usuario o el que se anotó para el invitado.
    public function run(): ?string
    {
        return $this->esInvitado() ? $this->invitado_run : $this->user?->run;
    }
}
