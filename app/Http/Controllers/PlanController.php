<?php

namespace App\Http\Controllers;

use App\Models\Ajuste;
use App\Models\PagoPro;
use App\Services\ActivarPro;
use App\Services\MercadoPago;
use App\Support\LigaActual;
use Illuminate\Http\Request;
use Throwable;

// Plan del campeonato actual: ver estado, pagar el Pro con Mercado Pago y volver del pago.
class PlanController extends Controller
{
    public function show(Request $request, MercadoPago $mp)
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $liga = LigaActual::liga();

        return view('campeonatos.plan', [
            'liga' => $liga,
            'equipos' => $liga->equipos()->count(),
            'precio' => Ajuste::precioProAnual(),
            'pagos' => $liga->pagosPro()->where('estado', 'aprobado')->with('user')->get(),
            'pagoDisponible' => $mp->configurado(),
            'modoPrueba' => $mp->modoPrueba(),
        ]);
    }

    // Crea el cobro en Mercado Pago y manda al usuario a pagar.
    public function pagar(Request $request, MercadoPago $mp)
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        if (! $mp->configurado()) {
            return back()->with('aviso', 'El pago en línea aún no está disponible. Escríbenos y lo activamos.');
        }

        $liga = LigaActual::liga();

        $pago = PagoPro::create([
            'liga_id' => $liga->id,
            'user_id' => $request->user()->id,
            'medio' => 'mercadopago',
            'estado' => 'pendiente',
            'monto' => Ajuste::precioProAnual(),
        ]);

        try {
            $preferencia = $mp->crearPreferencia($pago, $liga, $request->user());
        } catch (Throwable $e) {
            report($e);
            $pago->update(['estado' => 'rechazado', 'nota' => 'No se pudo crear el cobro']);

            return back()->with('aviso', 'No pudimos conectar con Mercado Pago. Intenta de nuevo en unos minutos.');
        }

        $pago->update(['mp_preferencia_id' => $preferencia['id']]);

        return redirect()->away($preferencia['url']);
    }

    // Mercado Pago devuelve aquí al usuario. Se consulta el pago a Mercado Pago (no se confía
    // en los parámetros de la URL) para activar al tiro, sin esperar el webhook.
    public function retorno(Request $request, MercadoPago $mp, ActivarPro $activar)
    {
        $pagoId = $request->query('payment_id');

        if ($pagoId && $pagoId !== 'null' && $mp->configurado()) {
            try {
                $pago = $activar->desdeMercadoPago($mp->obtenerPago((string) $pagoId));
            } catch (Throwable $e) {
                report($e);
                $pago = null;
            }

            if ($pago?->estado === 'aprobado') {
                return redirect()->route('campeonatos.plan')
                    ->with('status', '¡Listo! Tu campeonato ya es Pro hasta el '.$pago->pro_hasta->format('d/m/Y').'.');
            }
        }

        $mensaje = match ($request->query('status')) {
            'pending', 'in_process' => 'Tu pago quedó pendiente. Apenas Mercado Pago lo apruebe, el Pro se activa solo.',
            'rejected', 'failure' => 'El pago no se completó. Puedes intentarlo de nuevo.',
            default => 'Si completaste el pago, el Pro se activará en unos minutos.',
        };

        return redirect()->route('campeonatos.plan')->with('aviso', $mensaje);
    }
}
