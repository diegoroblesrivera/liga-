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
        'torneo_id', 'etapa_id', 'grupo', 'ronda', 'llave', 'numero_fecha', 'equipo_local_id', 'equipo_visita_id',
        'cancha_id', 'arbitro_id', 'fecha_hora', 'goles_local', 'goles_visita', 'penales_local', 'penales_visita',
        'estado', 'iniciado_en', 'finalizado_en','monto_pago',
    ];

    public const RONDAS = [
        'dieciseisavos' => 'Dieciseisavos de final',
        'octavos' => 'Octavos de final',
        'cuartos' => 'Cuartos de final',
        'semifinal' => 'Semifinal',
        'tercer_lugar' => 'Tercer lugar',
        'final' => 'Final',
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
        'iniciado_en' => 'datetime',
        'finalizado_en' => 'datetime',
        'goles_local' => 'integer',
        'goles_visita' => 'integer',
        'penales_local' => 'integer',
        'penales_visita' => 'integer',
        'llave' => 'integer',
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

    public function etapa(): BelongsTo
    {
        return $this->belongsTo(Etapa::class);
    }

    // Partido de eliminación directa (llave): no admite empate, se define a penales.
    public function esEliminacion(): bool
    {
        return $this->ronda !== null;
    }

    // "Fecha 3", "Grupo A · Fecha 2" o "Semifinal".
    public function etiqueta(): string
    {
        if ($this->ronda) {
            return self::RONDAS[$this->ronda] ?? ucfirst($this->ronda);
        }

        return ($this->grupo ? "Grupo {$this->grupo} · " : '').'Fecha '.$this->numero_fecha;
    }

    // Ganador de un partido finalizado (por goles y, si empataron, por penales).
    public function ganadorId(): ?int
    {
        if (! $this->estaFinalizado() || $this->goles_local === null) {
            return null;
        }
        $local = [$this->goles_local, $this->penales_local ?? 0];
        $visita = [$this->goles_visita, $this->penales_visita ?? 0];

        return match (true) {
            $local > $visita => $this->equipo_local_id,
            $visita > $local => $this->equipo_visita_id,
            default => null,
        };
    }

    public function perdedorId(): ?int
    {
        $ganador = $this->ganadorId();

        return $ganador === null ? null : ($ganador === $this->equipo_local_id ? $this->equipo_visita_id : $this->equipo_local_id);
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
