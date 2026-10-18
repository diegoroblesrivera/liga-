<?php

namespace App\Services;

use App\Models\Liga;
use App\Models\PagoPro;
use App\Models\User;
use Illuminate\Support\Facades\DB;

// Aplica el resultado de un pago al plan del campeonato. Idempotente: si Mercado Pago
// avisa dos veces el mismo pago (webhook + regreso del usuario), se activa una sola vez.
class ActivarPro
{
    /**
     * Procesa un pago según los datos que entrega Mercado Pago (GET /v1/payments/{id}).
     * Devuelve el PagoPro afectado, o null si el pago no es nuestro.
     */
    public function desdeMercadoPago(array $mp): ?PagoPro
    {
        $pagoId = (int) ($mp['external_reference'] ?? 0);
        if (! $pagoId) {
            return null;
        }

        return DB::transaction(function () use ($pagoId, $mp) {
            $pago = PagoPro::whereKey($pagoId)->lockForUpdate()->first();
            if (! $pago || $pago->estado === 'aprobado') {
                return $pago;
            }

            $estado = $mp['status'] ?? null;
            $montoOk = (int) round((float) ($mp['transaction_amount'] ?? 0)) >= $pago->monto
                && ($mp['currency_id'] ?? null) === 'CLP';

            if ($estado === 'approved' && $montoOk) {
                $liga = Liga::whereKey($pago->liga_id)->lockForUpdate()->first();
                [$desde, $hasta] = $liga->extenderProUnAnio();

                $pago->update([
                    'estado' => 'aprobado',
                    'mp_pago_id' => (string) $mp['id'],
                    'pagado_en' => now(),
                    'pro_desde' => $desde,
                    'pro_hasta' => $hasta,
                ]);
            } elseif (in_array($estado, ['rejected', 'cancelled', 'refunded', 'charged_back'], true)) {
                $pago->update(['estado' => 'rechazado', 'mp_pago_id' => (string) $mp['id'], 'nota' => 'Mercado Pago: '.$estado]);
            } elseif ($estado === 'approved') {
                // Aprobado pero con monto o moneda distintos: no se activa y queda anotado para revisar.
                $pago->update(['nota' => 'Monto o moneda no coinciden: '.($mp['transaction_amount'] ?? '?').' '.($mp['currency_id'] ?? '?')]);
            }

            return $pago->fresh();
        });
    }

    // Activación manual del super admin (por ejemplo, un pago por transferencia o una cortesía).
    public function manual(Liga $liga, User $admin, int $monto, ?string $nota = null): PagoPro
    {
        return DB::transaction(function () use ($liga, $admin, $monto, $nota) {
            $liga = Liga::whereKey($liga->id)->lockForUpdate()->first();
            [$desde, $hasta] = $liga->extenderProUnAnio();

            return PagoPro::create([
                'liga_id' => $liga->id,
                'user_id' => $admin->id,
                'medio' => 'manual',
                'estado' => 'aprobado',
                'monto' => $monto,
                'pagado_en' => now(),
                'pro_desde' => $desde,
                'pro_hasta' => $hasta,
                'nota' => $nota,
            ]);
        });
    }
}
