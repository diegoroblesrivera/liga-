<?php

namespace Tests\Feature;

use App\Models\Equipo;
use App\Models\Jugador;
use App\Models\Modalidad;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Requiere MySQL (las migraciones usan vistas, triggers y ALTER ... ENUM).
class SeccionesTest extends TestCase
{
    use RefreshDatabase;

    // Usuario que participa de verdad en el campeonato 1 con ese rol.
    protected function conRol(string $rol): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::firstOrCreate(['nombre' => $rol])->id);

        $equipo = Equipo::forceCreate([
            'liga_id' => 1,
            'nombre' => 'Equipo '.$user->id,
            'modalidad_id' => Modalidad::value('id'),
        ]);

        match ($rol) {
            'jugador' => Jugador::forceCreate(['equipo_id' => $equipo->id, 'user_id' => $user->id, 'nombre' => $user->name]),
            'dt' => $user->forceFill(['equipo_id' => $equipo->id])->save(),
            default => $user->ligas()->attach(1, ['rol' => $rol]), // admin / arbitro
        };

        return $user;
    }

    public function test_el_inicio_muestra_mis_torneos_y_mis_pichangas(): void
    {
        $this->actingAs($this->conRol('jugador'))->get('/dashboard')
            ->assertOk()
            ->assertSee('Mis torneos')
            ->assertSee('Ver mis torneos')
            ->assertSee('Mis pichangas')
            ->assertSee('Ver mis pichangas');
    }

    public function test_usuario_solo_de_pichangas_no_ve_ni_entra_al_campeonato(): void
    {
        $user = User::factory()->create(); // sin roles: entró con Google o registro normal

        $this->assertFalse($user->participaEnTorneos());

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee('No participas en ningún torneo')
            ->assertSee('Crear campeonato')
            ->assertSee('Ver mis pichangas')
            ->assertDontSee('Ver mis torneos')
            ->assertDontSee(route('tablas.posiciones'))
            ->assertDontSee(route('equipos.index'));

        foreach (['/posiciones', '/goleadores', '/equipos', '/partidos', '/mis-pagos', '/configuracion'] as $url) {
            $this->actingAs($user)->get($url)
                ->assertRedirect(route('dashboard'))
                ->assertSessionHas('status', 'Esa sección es solo para quienes participan en un torneo.');
        }

        // Pichangas y perfil siguen disponibles
        $this->actingAs($user)->get('/pichangas')->assertOk();
        $this->actingAs($user)->get('/profile')->assertOk();
    }

    public function test_quien_participa_en_el_torneo_entra_normal(): void
    {
        foreach (['jugador', 'dt', 'arbitro', 'admin'] as $rol) {
            $user = $this->conRol($rol);
            $this->assertTrue($user->participaEnTorneos(), $rol);

            $this->actingAs($user)->get('/equipos')->assertOk();
            $this->actingAs($user)->get('/dashboard')->assertSee(route('tablas.posiciones'));
        }

        $super = User::factory()->create(['is_super_admin' => true]);
        $this->assertTrue($super->participaEnTorneos());
    }

    public function test_la_inscripcion_por_invitacion_sigue_siendo_publica(): void
    {
        $this->get('/invitacion/token-inexistente')->assertNotFound();
    }
}
