<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Pichanga extends Model
{
    protected $table = 'pichangas';

    // Orden lunes → domingo; la clave es el dayOfWeek de Carbon.
    public const DIAS = [
        1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves',
        5 => 'Viernes', 6 => 'Sábado', 0 => 'Domingo',
    ];

    protected $fillable = [
        'nombre', 'descripcion', 'lugar', 'dia_semana', 'hora', 'duracion_min',
        'cupo', 'precio', 'organizador_id', 'token', 'activa',
    ];

    protected $casts = [
        'dia_semana' => 'integer',
        'duracion_min' => 'integer',
        'cupo' => 'integer',
        'precio' => 'integer',
        'activa' => 'boolean',
    ];

    public function organizador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizador_id');
    }

    public function miembros(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'pichanga_user')
            ->withPivot('rol')
            ->withTimestamps();
    }

    public function fechas(): HasMany
    {
        return $this->hasMany(PichangaFecha::class);
    }

    public function esMiembro(User $user): bool
    {
        return $this->miembros()->where('users.id', $user->id)->exists();
    }

    public function esOrganizador(User $user): bool
    {
        return $this->miembros()
            ->where('users.id', $user->id)
            ->wherePivot('rol', 'organizador')
            ->exists();
    }

    public function nombreDia(): string
    {
        return self::DIAS[$this->dia_semana];
    }

    // "21:00" en vez de "21:00:00".
    public function horaCorta(): string
    {
        return substr($this->hora, 0, 5);
    }

    // Próxima fecha pendiente (abierta o cerrada). Si no existe, la crea
    // para el próximo día que toca, saltando semanas ya jugadas o canceladas.
    public function proximaFecha(): PichangaFecha
    {
        $pendiente = $this->fechas()
            ->whereIn('estado', ['abierta', 'cerrada'])
            ->whereDate('fecha', '>=', today())
            ->orderBy('fecha')
            ->first();

        if ($pendiente) {
            return $pendiente;
        }

        $dia = today()->dayOfWeek === $this->dia_semana
            ? today()
            : today()->next($this->dia_semana);

        while ($this->fechas()->whereDate('fecha', $dia)->exists()) {
            $dia = $dia->copy()->addWeek();
        }

        return $this->fechas()->create([
            'fecha' => $dia->toDateString(),
            'hora' => $this->hora,
            'estado' => 'abierta',
        ]);
    }

    // Valor por persona según confirmados (redondeado hacia arriba).
    public function precioPorPersona(int $confirmados): ?int
    {
        if (! $this->precio || $confirmados === 0) {
            return null;
        }

        return (int) ceil($this->precio / $confirmados);
    }
}
