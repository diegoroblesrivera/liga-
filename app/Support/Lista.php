<?php

namespace App\Support;

use App\Models\Asistencia;
use App\Models\Jugador;
use App\Models\Partido;
use App\Models\Torneo;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Collection;

// Reglas de la lista de asistencia (quién la gestiona, quién queda bloqueado) y el QR.
class Lista
{
    // Ver el QR y la lista de una fecha: organizador, árbitro de algún partido de la
    // fecha o DT de un equipo que juega en ella.
    public static function puedeGestionarFecha(User $user, Collection $partidos): bool
    {
        return $user->hasRole('admin')
            || $partidos->contains(fn (Partido $p) => $p->arbitro_id === $user->id
                || ($user->equipo_id && in_array($user->equipo_id, [$p->equipo_local_id, $p->equipo_visita_id], true)));
    }

    // Marcar o quitar a un jugador a mano: organizador, árbitro del partido o DT de su equipo.
    public static function puedeMarcar(User $user, Partido $partido, ?Jugador $jugador = null): bool
    {
        if ($user->hasRole('admin') || $partido->arbitro_id === $user->id) {
            return true;
        }

        if (! $user->equipo_id || ! in_array($user->equipo_id, [$partido->equipo_local_id, $partido->equipo_visita_id], true)) {
            return false;
        }

        return $jugador === null || $jugador->equipo_id === $user->equipo_id;
    }

    /** @return array<int> ids de jugadores presentes en el partido */
    public static function presentes(Partido $partido): array
    {
        return Asistencia::where('partido_id', $partido->id)->pluck('jugador_id')->all();
    }

    // Con lista obligatoria, quien no marcó asistencia no puede aparecer en la planilla.
    public static function bloquea(Partido $partido, Jugador $jugador): bool
    {
        return (bool) LigaActual::liga()?->exigeLista()
            && ! Asistencia::where('partido_id', $partido->id)->where('jugador_id', $jugador->id)->exists();
    }

    // La lista abre el día del partido; sin horario definido queda abierta mientras no termine.
    public static function abierta(Partido $partido): bool
    {
        return in_array($partido->estado, ['programado', 'en_juego'], true)
            && ($partido->fecha_hora === null || $partido->fecha_hora->isToday());
    }

    public static function qrSvg(string $texto, int $tamano = 320): string
    {
        $svg = (new Writer(new ImageRenderer(new RendererStyle($tamano, 1), new SvgImageBackEnd)))->writeString($texto);

        return preg_replace('/^<\?xml.*?\?>\s*/', '', $svg);
    }

    public static function partidosDeFecha(Torneo $torneo, int $numero): Collection
    {
        return Partido::where('torneo_id', $torneo->id)
            ->where('numero_fecha', $numero)
            ->with(['equipoLocal.jugadores', 'equipoVisita.jugadores', 'asistencias'])
            ->orderBy('fecha_hora')
            ->get();
    }
}
