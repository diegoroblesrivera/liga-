<?php

namespace App\Http\Controllers;

use App\Services\ActivarPro;
use App\Services\MercadoPago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

// Avisos de Mercado Pago (notification_url). Solo trae el id del pago: el resultado real
// se consulta a Mercado Pago con nuestras credenciales antes de activar nada.
class WebhookMercadoPagoController extends Controller
{
    public function __invoke(Request $request, MercadoPago $mp, ActivarPro $activar)
    {
        if (! $mp->firmaValida($request)) {
            // Sin la clave: solo lo necesario para revisar por qué no coincide la firma.
            Log::warning('Webhook Mercado Pago con firma inválida', [
                'query' => $request->query(),
                'body_type' => $request->input('type'),
                'body_data_id' => $request->input('data.id'),
                'x_signature' => $request->header('x-signature'),
                'x_request_id' => $request->header('x-request-id'),
            ]);

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
