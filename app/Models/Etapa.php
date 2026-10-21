<?php

namespace App\Models;

use App\Models\Concerns\FiltradoPorLigaVia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Etapa de un torneo: liga (todos contra todos), grupos o eliminación directa.
class Etapa extends Model
{
    use FiltradoPorLigaVia;

    protected static array $ligaVia = ['torneo_id', Torneo::class];

    public const NOMBRES = [
        'liga' => 'Todos contra todos',
        'grupos' => 'Fase de grupos',
        'eliminacion' => 'Eliminación directa',
    ];

    protected $fillable = ['torneo_id', 'orden', 'tipo', 'ida_vuelta', 'grupos', 'clasifican', 'tercer_lugar', 'generada_en'];

    protected $casts = [
        'orden' => 'integer',
        'ida_vuelta' => 'boolean',
        'grupos' => 'integer',
        'clasifican' => 'integer',
        'tercer_lugar' => 'boolean',
        'generada_en' => 'datetime',
    ];

    public function torneo(): BelongsTo
    {
        return $this->belongsTo(Torneo::class);
    }

    public function partidos(): HasMany
    {
        return $this->hasMany(Partido::class);
    }

    public function equipos(): BelongsToMany
    {
        return $this->belongsToMany(Equipo::class, 'etapa_equipo')->withPivot('grupo', 'siembra');
    }

    public function esEliminacion(): bool
    {
        return $this->tipo === 'eliminacion';
    }

    public function nombre(): string
    {
        return self::NOMBRES[$this->tipo];
    }

    // Generada y con todos sus partidos jugados (o suspendidos).
    public function terminada(): bool
    {
        if (! $this->generada_en) {
            return false;
        }

        $partidos = $this->partidos()->get(['estado', 'ronda']);

        if ($partidos->isEmpty() || $partidos->contains(fn ($p) => in_array($p->estado, ['programado', 'en_juego'], true))) {
            return false;
        }

        // En eliminación, termina cuando se jugó la final.
        return ! $this->esEliminacion() || $partidos->contains(fn ($p) => $p->ronda === 'final' && $p->estado === 'finalizado');
    }
}
