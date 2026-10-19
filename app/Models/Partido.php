<?php

namespace App\Models;

use App\Models\Concerns\FiltradoPorLigaVia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partido extends Model
{
    use FiltradoPorLigaVia;

    // Pertenece al campeonato a través de su torneo.
    protected static array $ligaVia = ['torneo_id', Torneo::class];

    protected $table = 'partidos';

    protected $fillable = [
        'torneo_id', 'numero_fecha', 'equipo_local_id', 'equipo_visita_id',
        'cancha_id', 'arbitro_id', 'fecha_hora', 'goles_local', 'goles_visita',
        'estado', 'iniciado_en', 'finalizado_en','monto_pago',
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
        'iniciado_en' => 'datetime',
        'finalizado_en' => 'datetime',
        'goles_local' => 'integer',
        'goles_visita' => 'integer',
        'monto_pago' => 'decimal:2',
    ];

    // --- Relaciones ---

    public function torneo(): BelongsTo
    {
        return $this->belongsTo(Torneo::class);
    }

    public function equipoLocal(): BelongsTo
    {
        return $this->belongsTo(Equipo::class, 'equipo_local_id');
    }

    public function equipoVisita(): BelongsTo
    {
        return $this->belongsTo(Equipo::class, 'equipo_visita_id');
    }

    public function cancha(): BelongsTo
    {
        return $this->belongsTo(Cancha::class);
    }

    public function arbitro(): BelongsTo
    {
        return $this->belongsTo(User::class, 'arbitro_id');
    }

    public function goles(): HasMany
    {
        return $this->hasMany(Gol::class);
    }

    public function asistencias(): HasMany
    {
        return $this->hasMany(Asistencia::class);
    }

    // --- Estado del ciclo de vida ---

    public function estaProgramado(): bool
    {
        return $this->estado === 'programado';
    }

    public function estaEnJuego(): bool
    {
        return $this->estado === 'en_juego';
    }

    public function estaFinalizado(): bool
    {
        return $this->estado === 'finalizado';
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(PagoPartido::class);
    }
}
