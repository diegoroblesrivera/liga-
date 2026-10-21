<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Requiere MySQL (las migraciones usan vistas, triggers y ALTER ... ENUM).
class MenuLateralTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_super_admin_tiene_menu_lateral_y_los_demas_la_barra_superior(): void
    {
        $super = User::factory()->create(['is_super_admin' => true]);

        $this->actingAs($super)->get(route('dashboard'))->assertOk()
            ->assertSee('<aside', false)
            ->assertSeeInOrder(['Campeonato', 'Partidos', 'Posiciones', 'Pichangas', 'Panel TuTorneo', 'Métricas', 'Mi cuenta', 'Mis logros'])
            ->assertSee('lg:pl-64', false);

        $organizador = User::factory()->create();
        $organizador->ligas()->attach(1, ['rol' => 'admin']);
        $this->actingAs($organizador)->get(route('dashboard'))->assertOk()->assertDontSee('<aside', false);
    }
}
