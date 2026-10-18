<?php

namespace App\Http\Controllers;

use App\Services\ActivarPro;
use App\Services\MercadoPago;
use Illuminate\Http\Request;
use Throwable;

// Avisos de Mercado Pago (notification_url). Solo trae el id del pago: el resultado real
// se consulta a Mercado Pago con nuestras credenciales antes de activar nada.
class WebhookMercadoPagoController extends Controller
{
    public function __invoke(Request $request, MercadoPago $mp, ActivarPro $activar)
    {
        if (! $mp->firmaValida($request)) {
            return response()->json(['ok' => false, 'error' => 'firma inválida'], 401);
        }

        $tipo = $request->input('type', $request->query('type', $request->query('topic')));
        $pagoId = $request->input('data.id', $request->query('data_id', $request->query('id')));

        if ($tipo !== 'payment' || ! $pagoId || ! $mp->configurado()) {
            return response()->json(['ok' => true, 'ignorado' => true]);
        }

        try {
            $activar->desdeMercadoPago($mp->obtenerPago((string) $pagoId));
        } catch (Throwable $e) {
            report($e);

            // 500 para que Mercado Pago reintente más tarde.
            return response()->json(['ok' => false], 500);
        }

        return response()->json(['ok' => true]);
    }
}
