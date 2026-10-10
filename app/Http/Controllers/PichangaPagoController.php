<?php

namespace App\Http\Controllers;

use App\Models\PichangaAsistencia;
use App\Models\PichangaFecha;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PichangaPagoController extends Controller
{
    // El jugador confirmado sube su comprobante de transferencia: queda pagado.
    public function store(Request $request, PichangaFecha $fecha)
    {
        $asistencia = $fecha->asistencias()
            ->where('user_id', $request->user()->id)
            ->where('estado', 'confirmado')
            ->first();

        if (! $asistencia) {
            return back()->withErrors(['comprobante' => 'Solo los confirmados de esta fecha pueden subir comprobante.']);
        }

        if ($fecha->estado === 'cancelada') {
            return back()->withErrors(['comprobante' => 'Esta fecha fue cancelada.']);
        }

        $request->validate([
            'comprobante' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'comprobante.max' => 'La imagen no puede pesar más de 5 MB.',
        ]);

        $anterior = $asistencia->comprobante_path;

        // Nombre aleatorio y fuera de public/: no se puede adivinar ni abrir sin permiso.
        $ruta = $request->file('comprobante')->store('comprobantes-pichangas/'.$fecha->id, 'local');

        $asistencia->update([
            'comprobante_path' => $ruta,
            'comprobante_subido_en' => now(),
            'pagado' => true,
        ]);

        if ($anterior) {
            Storage::disk('local')->delete($anterior);
        }

        return back()->with('status', '¡Comprobante subido! Quedaste como pagado.');
    }

    // Ver el comprobante: solo quien lo subió o el organizador.
    public function show(Request $request, PichangaAsistencia $asistencia)
    {
        $user = $request->user();

        abort_unless(
            $asistencia->user_id === $user->id || $asistencia->fecha->pichanga->esOrganizador($user),
            403
        );

        abort_unless(
            $asistencia->comprobante_path && Storage::disk('local')->exists($asistencia->comprobante_path),
            404
        );

        return Storage::disk('local')->response($asistencia->comprobante_path);
    }
}
