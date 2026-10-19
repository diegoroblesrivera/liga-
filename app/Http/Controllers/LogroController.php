<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\Jugador;
use App\Models\Liga;
use App\Models\Logro;
use App\Services\Logros;
use App\Support\LigaActual;
use App\Support\Logros\Catalogo;
use App\Support\Logros\Insignias;
use Illuminate\Http\Request;

class LogroController extends Controller
{
    private const CLAVES_CAMPEONATO = ['jugador', 'torneo'];

    // Logros de un jugador en el campeonato actual (desde el plantel o los goleadores).
    public function jugador(Jugador $jugador, Logros $logros)
    {
        $jugador->load('equipo');
        $liga = LigaActual::liga();

        return view('logros.jugador', [
            'jugador' => $jugador,
            'liga' => $liga,
            'grilla' => $this->grillaFicha($jugador, $liga, $logros),
            'esMio' => $jugador->user_id === auth()->id(),
        ]);
    }

    // Todos mis logros: una sección por cada ficha (campeonato) y otra de pichangas.
    public function mios(Request $request, Logros $logros)
    {
        $user = $request->user();

        $fichas = Jugador::withoutGlobalScopes()->where('user_id', $user->id)->get()
            ->map(function (Jugador $ficha) use ($logros) {
                $equipo = Equipo::withoutGlobalScopes()->find($ficha->equipo_id);
                $liga = $equipo ? Liga::find($equipo->liga_id) : null;

                return $liga ? ['ficha' => $ficha, 'equipo' => $equipo, 'liga' => $liga, 'grilla' => $this->grillaFicha($ficha, $liga, $logros)] : null;
            })
            ->filter()
            ->values();

        $pichangas = Insignias::grilla(
            Logro::where('user_id', $user->id)->get(),
            Catalogo::clavesDe('pichanga'),
            $logros->progresoPichangas($user->id),
            true
        );

        return view('logros.mios', compact('fichas', 'pichangas'));
    }

    private function grillaFicha(Jugador $jugador, Liga $liga, Logros $logros)
    {
        $claves = array_merge(...array_map(fn ($a) => Catalogo::clavesDe($a), self::CLAVES_CAMPEONATO));

        return Insignias::grilla(
            Logro::where('jugador_id', $jugador->id)->get(),
            $claves,
            $logros->progresoJugador($jugador->id),
            $liga->esPro()
        );
    }
}
