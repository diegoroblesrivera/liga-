<?php

namespace Tests\Feature;

use App\Models\Ajuste;
use App\Models\Equipo;
use App\Models\Liga;
use App\Models\Modalidad;
use App\Models\PagoPro;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

// Requiere MySQL (las migraciones usan vistas, triggers y ALTER ... ENUM).
class PlanProTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Liga $liga;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->liga = Liga::create(['nombre' => 'Liga Plan', 'slug' => 'liga-plan', 'owner_id' => $this->admin->id, 'activa' => true, 'publica' => false]);
        $this->admin->ligas()->attach($this->liga->id, ['rol' => 'owner']);
        $this->admin->forceFill(['ultima_liga_id' => $this->liga->id])->save();

        config(['services.mercadopago.access_token' => 'TEST-123', 'services.mercadopago.webhook_secret' => null]);
    }

    protected function equipos(int $n): void
    {
        foreach (range(1, $n) as $i) {
            Equipo::forceCreate(['liga_id' => $this->liga->id, 'nombre' => "Equipo {$i}", 'modalidad_id' => Modalidad::value('id')]);
        }
    }

    protected function crearEquipo(string $nombre)
    {
        return $this->actingAs($this->admin)->post(route('equipos.store'), ['nombre' => $nombre, 'modalidad_id' => Modalidad::value('id')]);
    }

    public function test_el_plan_gratis_permite_8_equipos_y_bloquea_el_noveno(): void
    {
        $this->equipos(7);
        $this->crearEquipo('Octavo')->assertSessionHasNoErrors();
        $this->assertSame(8, $this->liga->equipos()->count());

        $this->actingAs($this->admin)->get(route('equipos.create'))->assertRedirect(route('campeonatos.plan'));
        $this->crearEquipo('Noveno')->assertRedirect(route('campeonatos.plan'))->assertSessionHas('aviso');
        $this->assertSame(8, $this->liga->equipos()->count());

        $this->actingAs($this->admin)->get(route('campeonatos.plan'))
            ->assertOk()->assertSee('Plan Gratis')->assertSee('8 de 8')->assertSee('$29.990');
    }

    public function test_con_pro_vigente_no_hay_limite_y_al_vencer_vuelve_el_limite(): void
    {
        $this->equipos(8);
        $this->liga->forceFill(['pro_hasta' => now()->addMonth()])->save();
        $this->crearEquipo('Noveno')->assertSessionHasNoErrors();
        $this->assertSame(9, $this->liga->equipos()->count());

        $this->liga->forceFill(['pro_hasta' => now()->subDay()])->save();
        $this->crearEquipo('Décimo')->assertRedirect(route('campeonatos.plan'));
    }

    public function test_pagar_crea_la_preferencia_en_mercado_pago_y_redirige(): void
    {
        Http::fake(['api.mercadopago.com/checkout/preferences' => Http::response([
            'id' => 'pref-1', 'init_point' => 'https://mp.test/real', 'sandbox_init_point' => 'https://mp.test/sandbox',
        ])]);

        $this->actingAs($this->admin)->post(route('campeonatos.plan.pagar'))->assertRedirect('https://mp.test/sandbox');

        $pago = PagoPro::firstOrFail();
        $this->assertSame('pendiente', $pago->estado);
        $this->assertSame(29990, $pago->monto);
        $this->assertSame('pref-1', $pago->mp_preferencia_id);

        Http::assertSent(fn ($r) => $r['external_reference'] === (string) $pago->id
            && $r['items'][0]['unit_price'] === 29990
            && $r['items'][0]['currency_id'] === 'CLP'
            && $r['notification_url'] === route('webhooks.mercadopago'));
    }

    public function test_el_webhook_activa_el_pro_solo_con_pago_aprobado_verificado_en_mercado_pago(): void
    {
        $pago = PagoPro::create(['liga_id' => $this->liga->id, 'user_id' => $this->admin->id, 'medio' => 'mercadopago', 'monto' => 29990]);

        Http::fake(['api.mercadopago.com/v1/payments/555' => Http::response([
            'id' => 555, 'status' => 'approved', 'external_reference' => (string) $pago->id,
            'transaction_amount' => 29990, 'currency_id' => 'CLP',
        ])]);

        $this->postJson(route('webhooks.mercadopago'), ['type' => 'payment', 'data' => ['id' => '555']])->assertOk();

        $this->assertSame('aprobado', $pago->fresh()->estado);
        $this->assertTrue($this->liga->fresh()->esPro());
        $this->assertTrue($this->liga->fresh()->pro_hasta->between(now()->addYear()->subMinute(), now()->addYear()->addMinute()));

        // Aviso repetido: no extiende dos veces
        $this->postJson(route('webhooks.mercadopago'), ['type' => 'payment', 'data' => ['id' => '555']])->assertOk();
        $this->assertTrue($this->liga->fresh()->pro_hasta->lessThan(now()->addYear()->addDay()));
    }

    public function test_no_se_activa_con_monto_distinto_ni_con_pago_rechazado(): void
    {
        $barato = PagoPro::create(['liga_id' => $this->liga->id, 'medio' => 'mercadopago', 'monto' => 29990]);
        $rechazado = PagoPro::create(['liga_id' => $this->liga->id, 'medio' => 'mercadopago', 'monto' => 29990]);

        Http::fake([
            'api.mercadopago.com/v1/payments/1' => Http::response(['id' => 1, 'status' => 'approved', 'external_reference' => (string) $barato->id, 'transaction_amount' => 100, 'currency_id' => 'CLP']),
            'api.mercadopago.com/v1/payments/2' => Http::response(['id' => 2, 'status' => 'rejected', 'external_reference' => (string) $rechazado->id, 'transaction_amount' => 29990, 'currency_id' => 'CLP']),
        ]);

        $this->postJson(route('webhooks.mercadopago'), ['type' => 'payment', 'data' => ['id' => '1']])->assertOk();
        $this->postJson(route('webhooks.mercadopago'), ['type' => 'payment', 'data' => ['id' => '2']])->assertOk();

        $this->assertSame('pendiente', $barato->fresh()->estado);
        $this->assertStringContainsString('no coinciden', $barato->fresh()->nota);
        $this->assertSame('rechazado', $rechazado->fresh()->estado);
        $this->assertFalse($this->liga->fresh()->esPro());
    }

    public function test_con_clave_secreta_el_webhook_rechaza_firmas_invalidas(): void
    {
        config(['services.mercadopago.webhook_secret' => 'secreto']);
        Http::fake(['api.mercadopago.com/v1/payments/9' => Http::response(['id' => 9, 'status' => 'pending', 'external_reference' => '0'])]);

        $this->postJson(route('webhooks.mercadopago').'?data.id=9&type=payment', ['type' => 'payment', 'data' => ['id' => '9']],
            ['x-signature' => 'ts=1,v1=falsa', 'x-request-id' => 'r1'])->assertStatus(401);
        Http::assertNothingSent();

        $ts = '1700000000';
        $firma = hash_hmac('sha256', 'id:9;request-id:r1;ts:'.$ts.';', 'secreto');
        $this->postJson(route('webhooks.mercadopago').'?data.id=9&type=payment', ['type' => 'payment', 'data' => ['id' => '9']],
            ['x-signature' => "ts={$ts},v1={$firma}", 'x-request-id' => 'r1'])->assertOk();
    }

    public function test_renovar_antes_de_vencer_suma_el_anio_al_final(): void
    {
        $vence = now()->addMonths(2)->startOfSecond();
        $this->liga->forceFill(['pro_hasta' => $vence])->save();

        [$desde, $hasta] = $this->liga->fresh()->extenderProUnAnio();

        $this->assertTrue($desde->equalTo($vence));
        $this->assertTrue($hasta->equalTo($vence->copy()->addYear()));
    }

    public function test_panel_de_super_admin(): void
    {
        $super = User::factory()->create(['is_super_admin' => true]);

        $this->actingAs($this->admin)->get(route('admin.campeonatos'))->assertForbidden();
        $this->actingAs($super)->get(route('admin.campeonatos'))->assertOk()->assertSee('Liga Plan');

        $this->actingAs($super)->post(route('admin.campeonatos.pro', $this->liga), ['monto' => 0, 'nota' => 'Cortesía'])->assertRedirect();
        $this->assertTrue($this->liga->fresh()->esPro());
        $this->assertSame('manual', PagoPro::first()->medio);

        $this->actingAs($super)->delete(route('admin.campeonatos.pro.quitar', $this->liga))->assertRedirect();
        $this->assertFalse($this->liga->fresh()->esPro());

        $this->actingAs($super)->put(route('admin.precio'), ['precio' => 34990])->assertRedirect();
        $this->assertSame(34990, Ajuste::precioProAnual());
    }
}
