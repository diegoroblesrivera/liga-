<?php

namespace Tests\Feature;

use App\Models\Equipo;
use App\Models\Modalidad;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Requiere MySQL (las migraciones usan vistas, triggers y ALTER ... ENUM).
class CompartirWhatsappTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_link_de_jugadores_se_puede_mandar_por_whatsapp_con_mensaje_listo(): void
    {
        $equipo = Equipo::forceCreate(['liga_id' => 1, 'nombre' => 'Los Leones', 'modalidad_id' => Modalidad::value('id')]);
        $dt = User::factory()->create(['equipo_id' => $equipo->id]);
        $dt->roles()->attach(Role::firstOrCreate(['nombre' => 'dt'])->id);

        $this->actingAs($dt)->from(route('equipos.show', $equipo))
            ->post(route('invitaciones.store', $equipo), ['tipo' => 'jugador'])
            ->assertSessionHas('mensaje_invitacion', fn ($m) => str_contains($m, 'Súmate al plantel de *Los Leones*') && str_contains($m, '/invitacion/'));

        $this->actingAs($dt)->get(route('equipos.show', $equipo))
            ->assertSee('https://wa.me/?text=', false)
            ->assertSee(rawurlencode('*Los Leones*'), false)
            ->assertSee('Copiar');
    }

    public function test_el_link_de_arbitro_tambien(): void
    {
        $admin = User::factory()->create();
        $admin->ligas()->attach(1, ['rol' => 'admin']);

        $this->actingAs($admin)->from(route('arbitros.index'))->post(route('arbitros.invitar'))
            ->assertSessionHas('mensaje_invitacion', fn ($m) => str_contains($m, 'ser árbitro del campeonato'));
        $this->actingAs($admin)->get(route('arbitros.index'))->assertSee('https://wa.me/?text=', false);
    }
}
