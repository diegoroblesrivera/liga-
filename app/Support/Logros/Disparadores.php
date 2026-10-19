<?php

namespace App\Support\Logros;

use App\Models\Asistencia;
use App\Models\Gol;
use App\Models\Liga;
use App\Models\Partido;
use App\Models\PichangaFecha;
use App\Models\Tarjeta;
use App\Models\Torneo;
use App\Services\Logros;
use Illuminate\Support\Facades\DB;

// Cuándo se recalculan los logros (se registra en AppServiceProvider).
class Disparadores
{
    public static function registrar(): void
    {
        // Partido finalizado, corregido, reabierto o borrado.
        Partido::saved(function (Partido $p) {
            if ($p->estado === 'finalizado' || $p->wasChanged('estado')) {
                Logros::seguro(fn (Logros $l) => $l->despuesDePartido($p));
            }
        });
        Partido::deleted(fn (Partido $p) => Logros::seguro(fn (Logros $l) => $l->despuesDePartido($p)));

        // Correcciones de goles, tarjetas o lista en un partido ya finalizado.
        foreach ([Gol::class, Tarjeta::class, Asistencia::class] as $modelo) {
            $alCambiar = function ($registro) {
                $partido = Partido::withoutGlobalScopes()->find($registro->partido_id);
                if ($partido?->estaFinalizado()) {
                    Logros::seguro(fn (Logros $l) => $l->despuesDePartido($partido));
                }
            };
            $modelo::saved($alCambiar);
            $modelo::deleted($alCambiar);
        }

        // Pichanga jugada (planilla guardada) o que deja de estarlo.
        PichangaFecha::saved(function (PichangaFecha $f) {
            if ($f->estado === 'jugada' || $f->wasChanged('estado')) {
                $usuarios = DB::table('pichanga_asistencias')->where('pichanga_fecha_id', $f->id)->whereNotNull('user_id')->pluck('user_id');
                Logros::seguro(fn (Logros $l) => $usuarios->each(fn ($id) => $l->recalcularUsuarioPichangas($id)));
            }
        });

        // Torneo cerrado (o reabierto) por el organizador.
        Torneo::saved(function (Torneo $t) {
            if ($t->wasChanged('cerrado_en')) {
                Logros::seguro(fn (Logros $l) => $l->recalcularTorneo($t->id));
            }
        });

        // Al pasar a Pro se otorgan los logros Pro que ya se habían ganado con los datos.
        Liga::saved(function (Liga $liga) {
            if ($liga->wasChanged('pro_hasta') && $liga->esPro()) {
                Logros::seguro(fn (Logros $l) => $l->recalcularLiga($liga->id));
            }
        });
    }
}
