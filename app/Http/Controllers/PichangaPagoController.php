<?php

namespace App\Http\Controllers;

use App\Models\PichangaAsistencia;
use App\Models\PichangaFecha;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PichangaPagoController extends Controller
{
    // El jugador confirmado sube su comprobante de transferencia: queda pagado, y también
    // quienes marque en "Pago también por" (sus invitados u otros que aún no pagan).
    public function store(Request $request, PichangaFecha $fecha)
    {
        $user = $request->user();

        $asistencia = $fecha->asistencias()
            ->where('user_id', $user->id)
            ->where('estado', 'confirmado')
            ->first();

        if (! $asistencia) {
            return back()->withErrors(['comprobante' => 'Solo los confirmados de esta fecha pueden subir comprobante.']);
        }

        if ($fecha->estado === 'cancelada') {
            return back()->withErrors(['comprobante' => 'Esta fecha fue cancelada.']);
        }

        $data = $request->validate([
            'comprobante' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'pago_por' => ['array'],
            'pago_por.*' => ['integer'],
        ], [
            'comprobante.max' => 'La imagen no puede pesar más de 10 MB.',
            'comprobante.uploaded' => 'No se pudo subir la imagen. Si es una foto muy pesada, prueba con una captura de pantalla.',
        ]);

        // Por quiénes más puede pagar: sus propios invitados, o confirmados con cuenta que
        // aún no pagan. Cualquier otro ID enviado se ignora.
        $otros = $fecha->confirmados()
            ->whereIn('id', $data['pago_por'] ?? [])
            ->whereKeyNot($asistencia->id)
            ->where(fn ($q) => $q->where('invitado_por', $user->id)
                ->orWhere(fn ($q) => $q->whereNull('invitado_por')->where('pagado', false)))
            ->get();

        $cubiertos = collect([$asistencia])->concat($otros);
        $anteriores = $cubiertos->pluck('comprobante_path')->filter()->unique();

        // Nombre aleatorio y fuera de public/: no se puede adivinar ni abrir sin permiso.
        $ruta = $request->file('comprobante')->store('comprobantes-pichangas/'.$fecha->id, 'local');

        foreach ($cubiertos as $a) {
            $a->update([
                'comprobante_path' => $ruta,
                'comprobante_subido_en' => now(),
                'pagado' => true,
                'pagado_por' => $a->is($asistencia) ? null : $user->id,
            ]);
        }

        // Un comprobante anterior se borra solo si ya nadie lo usa.
        foreach ($anteriores as $anterior) {
            if (! PichangaAsistencia::where('comprobante_path', $anterior)->exists()) {
                Storage::disk('local')->delete($anterior);
            }
        }

        $mensaje = $otros->isEmpty()
            ? '¡Comprobante subido! Quedaste como pagado.'
            : '¡Comprobante subido! Quedaron pagados tú y '.$otros->count().' '.($otros->count() === 1 ? 'persona más' : 'personas más').'.';

        return back()->with('status', $mensaje);
    }

    // Ver el comprobante: el jugador, quien pagó por él, quien lo invitó, o el organizador.
    public function show(Request $request, PichangaAsistencia $asistencia)
    {
        $user = $request->user();

        abort_unless(
            in_array($user->id, [$asistencia->user_id, $asistencia->pagado_por, $asistencia->invitado_por], true)
                || $asistencia->fecha->pichanga->esOrganizador($user),
            403
        );

        abort_unless(
            $asistencia->comprobante_path && Storage::disk('local')->exists($asistencia->comprobante_path),
            404
        );

        return Storage::disk('local')->response($asistencia->comprobante_path);
    }
}
