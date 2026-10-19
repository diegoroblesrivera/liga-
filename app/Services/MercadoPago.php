<?php

namespace App\Services;

use App\Models\Liga;
use App\Models\PagoPro;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Integración mínima con Mercado Pago Checkout Pro (pago único, CLP).
 * - crearPreferencia(): arma el cobro y devuelve la URL donde paga el usuario.
 * - obtenerPago(): consulta el pago DIRECTO a Mercado Pago (fuente de verdad).
 * - firmaValida(): valida la firma de las notificaciones (webhooks).
 */
class MercadoPago
{
    protected const API = 'https://api.mercadopago.com';

    public function configurado(): bool
    {
        return filled(config('services.mercadopago.access_token'));
    }

    // Credenciales de prueba: token TEST- (antiguas) o MP_PRUEBA=true (cuenta TESTUSER).
    public function modoPrueba(): bool
    {
        return $this->tokenSandbox() || config('services.mercadopago.prueba');
    }

    protected function tokenSandbox(): bool
    {
        return str_starts_with((string) config('services.mercadopago.access_token'), 'TEST-');
    }

    /** @return array{id: string, url: string} */
    public function crearPreferencia(PagoPro $pago, Liga $liga, User $user): array
    {
        $datos = [
            'items' => [[
                'id' => 'tutorneo-pro-anual',
                'title' => config('app.name').' Pro anual · '.$liga->nombre,
                'description' => 'Plan Pro por 1 año para el campeonato '.$liga->nombre,
                'quantity' => 1,
                'currency_id' => 'CLP',
                'unit_price' => $pago->monto,
            ]],
            'external_reference' => (string) $pago->id,
            'back_urls' => [
                'success' => route('campeonatos.plan.retorno'),
                'pending' => route('campeonatos.plan.retorno'),
                'failure' => route('campeonatos.plan.retorno'),
            ],
            'statement_descriptor' => 'TUTORNEO',
        ];

        // En prueba no se manda el comprador: un email real contra un vendedor de prueba
        // hace que Mercado Pago bloquee el pago ("una de las partes es de prueba").
        if (! $this->modoPrueba()) {
            $datos['payer'] = ['email' => $user->email, 'name' => $user->name];
        }

        // Mercado Pago no acepta avisos ni vuelta automática a localhost; en local
        // se vuelve con el botón "Volver al sitio" y el retorno activa el Pro igual.
        if (str_starts_with(route('webhooks.mercadopago'), 'https://')) {
            $datos['auto_return'] = 'approved';
            $datos['notification_url'] = route('webhooks.mercadopago');
        }

        $respuesta = $this->http()->post(self::API.'/checkout/preferences', $datos);

        if (! $respuesta->successful()) {
            throw new RuntimeException('Mercado Pago rechazó la preferencia: '.$respuesta->status().' '.$respuesta->body());
        }

        return [
            'id' => (string) $respuesta->json('id'),
            'url' => $this->tokenSandbox()
                ? ($respuesta->json('sandbox_init_point') ?? $respuesta->json('init_point'))
                : $respuesta->json('init_point'),
        ];
    }

    // Datos reales del pago según Mercado Pago (status, external_reference, monto, moneda).
    public function obtenerPago(string $pagoId): array
    {
        $respuesta = $this->http()->get(self::API.'/v1/payments/'.urlencode($pagoId));

        if (! $respuesta->successful()) {
            throw new RuntimeException('No se pudo consultar el pago '.$pagoId.' en Mercado Pago: '.$respuesta->status());
        }

        $pago = $respuesta->json();

        if (! is_array($pago)) {
            throw new RuntimeException('Respuesta inválida de Mercado Pago para el pago '.$pagoId);
        }

        return $pago;
    }

    /**
     * Firma de las notificaciones: header x-signature "ts=...,v1=..." con HMAC-SHA256 de
     * "id:{data.id};request-id:{x-request-id};ts:{ts};" usando la clave secreta del webhook.
     * Sin clave configurada no se puede validar: se acepta, porque el pago igual se
     * verifica consultando a Mercado Pago con el access token.
     */
    public function firmaValida(Request $request): bool
    {
        $secreto = config('services.mercadopago.webhook_secret');
        if (blank($secreto)) {
            return true;
        }

        $partes = collect(explode(',', (string) $request->header('x-signature')))
            ->mapWithKeys(function ($parte) {
                [$k, $v] = array_pad(explode('=', trim($parte), 2), 2, null);

                return [$k => $v];
            });

        $ts = $partes->get('ts');
        $v1 = $partes->get('v1');
        if (! $ts || ! $v1) {
            return false;
        }

        $id = strtolower((string) $request->query('data_id', $request->input('data.id', '')));
        $manifiesto = "id:{$id};request-id:".$request->header('x-request-id').";ts:{$ts};";

        return hash_equals(hash_hmac('sha256', $manifiesto, $secreto), $v1);
    }

    protected function http()
    {
        if (! $this->configurado()) {
            throw new RuntimeException('Mercado Pago no está configurado (falta MP_ACCESS_TOKEN).');
        }

        return Http::withToken(config('services.mercadopago.access_token'))
            ->acceptJson()
            ->timeout(15);
    }
}
