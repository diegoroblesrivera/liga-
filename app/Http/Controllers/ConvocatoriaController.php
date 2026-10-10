<?php

namespace App\Http\Controllers;

use App\Models\Convocatoria;
use App\Models\Equipo;
use App\Models\Partido;
use App\Support\Formacion;
use App\Support\Suspension;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConvocatoriaController extends Controller
{
    public function edit(Partido $partido, Equipo $equipo)
    {
        $this->autorizar($partido, $equipo);

        $equipo->load('jugadores', 'modalidad');

        $limite = Formacion::enCancha($equipo);
        $suspendidos = Suspension::suspendidosEnPartido($partido, $equipo->id);

        $convocados = Convocatoria::where('partido_id', $partido->id)
            ->whereIn('jugador_id', $equipo->jugadores->pluck('id'))
            ->get()
            ->keyBy('jugador_id');

        $arqueroFijo = $equipo->jugadores->first(
            fn ($j) => preg_match('/arquer|porter|goler|arco/i', (string) $j->posicion)
        );

        // Convocatoria por defecto (si aún no hay guardada): arquero + primeros
        // por dorsal como titulares, resto suplentes, saltando suspendidos.
        $defaults = [];
        if ($convocados->isEmpty()) {
            $ordenados = $equipo->jugadores->sortBy(fn ($j) => $j->dorsal ?? 999)->values();

            $titularIds = [];
            if ($arqueroFijo && ! in_array($arqueroFijo->id, $suspendidos)) {
                $titularIds[] = $arqueroFijo->id;
            }
            foreach ($ordenados as $j) {
                if (count($titularIds) >= $limite) {
                    break;
                }
                if (in_array($j->id, $suspendidos)) {
                    continue;
                }
                if (! in_array($j->id, $titularIds)) {
                    $titularIds[] = $j->id;
                }
            }

            foreach ($equipo->jugadores as $j) {
                if (in_array($j->id, $suspendidos)) {
                    $defaults[$j->id] = 'no';
                } else {
                    $defaults[$j->id] = in_array($j->id, $titularIds) ? 'titular' : 'suplente';
                }
            }
        }

        $arqueroConvId = optional($convocados->firstWhere('es_arquero', true))->jugador_id;

        return view('convocatorias.edit', compact(
            'partido', 'equipo', 'limite', 'convocados', 'arqueroFijo',
            'defaults', 'arqueroConvId', 'suspendidos'
        ));
    }

    public function update(Request $request, Partido $partido, Equipo $equipo)
    {
        $this->autorizar($partido, $equipo);

        $equipo->load('jugadores');
        $limite = Formacion::enCancha($equipo);
        $jugadoresIds = $equipo->jugadores->pluck('id')->all();

        $request->validate([
            'estado' => ['array'],
            'estado.*' => ['in:titular,suplente,no'],
            'arquero_id' => ['nullable', 'integer'],
        ]);

        $estados = $request->input('estado', []);

        $titulares = [];
        $suplentes = [];
        foreach ($jugadoresIds as $jid) {
            $e = $estados[$jid] ?? 'no';
            if ($e === 'titular') {
                $titulares[] = $jid;
            } elseif ($e === 'suplente') {
                $suplentes[] = $jid;
            }
        }

        // No se puede convocar a un jugador suspendido.
        $suspendidos = Suspension::suspendidosEnPartido($partido, $equipo->id);
        $convocadosIds = array_merge($titulares, $suplentes);
        if (array_intersect($suspendidos, $convocadosIds)) {
            return back()
                ->withErrors(['suspendidos' => 'Hay jugadores suspendidos que no pueden ser convocados a este partido.'])
                ->withInput();
        }

        if (count($titulares) > $limite) {
            return back()
                ->withErrors(['titulares' => "No puedes convocar más de {$limite} titulares para esta modalidad."])
                ->withInput();
        }

        $arqueroFijo = $equipo->jugadores->first(
            fn ($j) => preg_match('/arquer|porter|goler|arco/i', (string) $j->posicion)
        );

        $arqueroId = null;
        if ($arqueroFijo && in_array($arqueroFijo->id, $titulares)) {
            $arqueroId = $arqueroFijo->id;
        } elseif (! $arqueroFijo && $request->filled('arquero_id') && in_array((int) $request->arquero_id, $titulares)) {
            $arqueroId = (int) $request->arquero_id;
        }

        DB::transaction(function () use ($partido, $jugadoresIds, $titulares, $suplentes, $arqueroId) {
            Convocatoria::where('partido_id', $partido->id)
                ->whereIn('jugador_id', $jugadoresIds)
                ->delete();

            foreach ($titulares as $jid) {
                Convocatoria::create([
                    'partido_id' => $partido->id,
                    'jugador_id' => $jid,
                    'es_titular' => true,
                    'es_arquero' => $jid === $arqueroId,
                ]);
            }

            foreach ($suplentes as $jid) {
                Convocatoria::create([
                    'partido_id' => $partido->id,
                    'jugador_id' => $jid,
                    'es_titular' => false,
                    'es_arquero' => false,
                ]);
            }
        });

        return redirect()
            ->route('convocatorias.edit', [$partido, $equipo])
            ->with('status', 'Convocatoria guardada.');
    }

    public function alineacion(Partido $partido, Equipo $equipo)
    {
        $this->autorizar($partido, $equipo);

        $equipo->load('modalidad');

        $data = Formacion::delPartido($partido, $equipo);

        return view('equipos.alineacion', [
            'equipo' => $equipo,
            'arquero' => $data['arquero'],
            'lineas' => $data['lineas'],
        ]);
    }

    private function autorizar(Partido $partido, Equipo $equipo): void
    {
        abort_unless(
            in_array($equipo->id, [$partido->equipo_local_id, $partido->equipo_visita_id]),
            404,
            'Ese equipo no juega este partido.'
        );

        $user = auth()->user();
        $ok = $user->hasRole('admin') || $user->equipo_id === $equipo->id;

        abort_unless($ok, 403, 'No puedes convocar para este equipo.');
    }
}
