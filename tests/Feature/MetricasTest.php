<?php

namespace Tests\Feature;

use App\Models\Equipo;
use App\Models\Liga;
use App\Models\Modalidad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

// Requiere MySQL (las migraciones usan vistas, triggers y ALTER ... ENUM).
class MetricasTest extends TestCase
{
    use RefreshDatabase;

    public function test_registra_el_ultimo_acceso_y_un_dia_activo_sin_escribir_en_cada_visita(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
        $primero = $user->fresh()->ultimo_acceso;
        $this->assertNotNull($primero);
        $this->assertSame(1, DB::table('actividad_diaria')->where('user_id', $user->id)->count());

        // Otra visita a los pocos minutos: no vuelve a escribir
        $this->travel(2)->minutes();
        $this->actingAs($user->fresh())->get(route('dashboard'));
        $this->assertTrue($user->fresh()->ultimo_acceso->equalTo($primero));

        // Al día siguiente: nuevo día activo
        $this->travel(1)->days();
        $this->actingAs($user->fresh())->get(route('dashboard'));
        $this->assertSame(2, DB::table('actividad_diaria')->where('user_id', $user->id)->count());
    }

    public function test_el_panel_de_metricas_es_solo_del_super_admin_y_muestra_los_datos(): void
    {
        $super = User::factory()->create(['is_super_admin' => true]);
        $dueno = User::factory()->create(['name' => 'Dueña Cerca']);
        $liga = Liga::create(['nombre' => 'Liga Casi Llena', 'slug' => 'casi-llena', 'owner_id' => $dueno->id, 'activa' => true, 'publica' => false]);
        foreach (range(1, 7) as $i) {
            Equipo::forceCreate(['liga_id' => $liga->id, 'nombre' => "E{$i}", 'modalidad_id' => Modalidad::value('id')]);
        }

        $this->actingAs($dueno)->get(route('admin.metricas'))->assertForbidden();

        $this->actingAs($super)->get(route('admin.metricas'))
            ->assertOk()
            ->assertSee('Usuarios activos por semana')
            ->assertSee('Liga Casi Llena')
            ->assertSee('7/8');
    }

    public function test_el_script_de_analitica_solo_va_en_produccion_con_token(): void
    {
        config(['services.cloudflare_analytics' => 'abc123']);
        $this->get(route('home'))->assertDontSee('cloudflareinsights', false);

        $this->app['env'] = 'production';
        $this->get(route('home'))->assertSee('cloudflareinsights', false)->assertSee('"token": "abc123"', false);
    }
}
