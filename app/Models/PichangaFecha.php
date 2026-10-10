<?php

namespace App\Models;

use App\Support\Run;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class PichangaFecha extends Model
{
    protected $table = 'pichanga_fechas';

    protected $fillable = ['pichanga_id', 'fecha', 'hora', 'estado', 'goles_a', 'goles_b'];

    protected $casts = [
        'fecha' => 'date',
        'goles_a' => 'integer',
        'goles_b' => 'integer',
    ];

    public function pichanga(): BelongsTo
    {
        return $this->belongsTo(Pichanga::class);
    }

    public function asistencias(): HasMany
    {
        return $this->hasMany(PichangaAsistencia::class);
    }

    public function confirmados(): HasMany
    {
        return $this->asistencias()->where('estado', 'confirmado')->orderBy('anotado_en');
    }

    public function enEspera(): HasMany
    {
        return $this->asistencias()->where('estado', 'espera')->orderBy('anotado_en');
    }

    public function estaAbierta(): bool
    {
        return $this->estado === 'abierta';
    }

    public function fechaLarga(): string
    {
        return $this->fecha->locale('es')->translatedFormat('l j \d\e F');
    }

    // Lista para la portería: confirmados en orden de llegada, con RUN, patente y acompañantes.
    public function listaAcceso(): string
    {
        $confirmados = $this->confirmados()->with('user.acompanantes')->get();

        return $confirmados->values()->map(function ($asistencia, $i) {
            $user = $asistencia->user;

            $lineas = [
                ($i + 1).'.- '.$user->name,
                'Run '.($user->run ? Run::formatear($user->run) : '(sin registrar)'),
            ];

            if ($user->patente) {
                $lineas[] = 'Patente '.$user->patente;
            }

            if ($user->acompanantes->isNotEmpty()) {
                $lineas[] = 'Acompañantes';

                foreach ($user->acompanantes as $acompanante) {
                    $lineas[] = $acompanante->nombre;
                    $lineas[] = 'Run '.Run::formatear($acompanante->run);
                }
            }

            return implode("\n", $lineas);
        })->implode("\n");
    }

    // "Voy": entra como confirmado si hay cupo, si no a la lista de espera.
    public function anotar(User $user): PichangaAsistencia
    {
        return DB::transaction(function () use ($user) {
            // Bloquea la fecha para que dos personas no tomen el último cupo a la vez.
            self::whereKey($this->id)->lockForUpdate()->first();

            $asistencia = $this->asistencias()->firstOrNew(['user_id' => $user->id]);

            if (in_array($asistencia->estado, ['confirmado', 'espera'], true)) {
                return $asistencia;
            }

            $hayCupo = $this->confirmados()->count() < $this->pichanga->cupo;

            $asistencia->fill([
                'estado' => $hayCupo ? 'confirmado' : 'espera',
                'anotado_en' => now(),
            ])->save();

            return $asistencia;
        });
    }

    // "No voy": se baja y, si liberó un cupo, sube el primero de la lista de espera.
    public function bajar(User $user): void
    {
        DB::transaction(function () use ($user) {
            self::whereKey($this->id)->lockForUpdate()->first();

            $asistencia = $this->asistencias()->firstOrNew(['user_id' => $user->id]);
            $liberaCupo = $asistencia->estado === 'confirmado';

            $asistencia->fill([
                'estado' => 'baja',
                'equipo' => null,
                'anotado_en' => $asistencia->anotado_en ?? now(),
            ])->save();

            if ($liberaCupo) {
                $this->enEspera()->first()?->update(['estado' => 'confirmado']);
            }
        });
    }
}
