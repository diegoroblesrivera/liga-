<?php

namespace Tests\Feature;

use App\Models\Equipo;
use App\Models\Gol;
use App\Models\Jugador;
use App\Models\Logro;
use App\Models\Modalidad;
use App\Models\Partido;
use App\Models\Torneo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Requiere MySQL (las migraciones usan vistas, triggers y ALTER ... ENUM).
class LogrosVistasTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Jugador $pedro;
    protected Equipo $equipo;

    protected function setUp(): void
    {
        parent::setUp();

        $modalidad = Modalidad::value('id');
        $this->equipo = Equipo::forceCreate(['liga_id' => 1, 'nombre' => 'Leones', 'modalidad_id' => $modalidad]);
        $rival = Equipo::forceCreate(['liga_id' => 1, 'nombre' => 'Tigres', 'modalidad_id' => $modalidad]);
        $torneo = Torneo::forceCreate(['liga_id' => 1, 'nombre' => 'Apertura']);

        $this->user = User::factory()->create();
        $this->pedro = Jugador::withoutGlobalScopes()->forceCreate(['equipo_id' => $this->equipo->id, 'user_id' => $this->user->id, 'nombre' => 'Pedro Pérez', 'activo' => true]);

        // Un partido con 3 goles de Pedro: debut, primer gol y hat-trick
        $p = Partido::withoutGlobalScopes()->forceCreate([
            'torneo_id' => $torneo->id, 'numero_fecha' => 1, 'equipo_local_id' => $this->equipo->id,
            'equipo_visita_id' => $rival->id, 'estado' => 'en_juego', 'fecha_hora' => now()->subDay(),
        ]);
        for ($i = 0; $i < 3; $i++) {
            Gol::withoutGlobalScopes()->forceCreate(['partido_id' => $p->id, 'jugador_id' => $this->pedro->id, 'equipo_id' => $this->equipo->id, 'tipo' => 'normal']);
        }
        $p->update(['estado' => 'finalizado', 'goles_local' => 3, 'goles_visita' => 0]);
    }

    public function test_la_ficha_muestra_obtenidos_avance_y_los_pro_bloqueados(): void
    {
        $this->actingAs($this->user)->get(route('jugadores.logros', $this->pedro))
            ->assertOk()
            ->assertSee('Pedro Pérez')
            ->assertSeeText('3 de 16 logros')
            ->assertSee('Hat-trick')
            ->assertSee(now()->subDay()->format('d/m/Y'))
            ->assertSee('3/10 goles')
            ->assertSee('1/10 partidos')
            ->assertSee('Solo en campeonatos Pro');
    }

    public function test_mis_logros_junta_campeonatos_y_pichangas(): void
    {
        $this->actingAs($this->user)->get(route('logros.mios'))
            ->assertOk()
            ->assertSee('Leones')
            ->assertSee('Pichangas')
            ->assertSee('0/10 pichangas');

        // Quien solo juega pichangas también tiene su página
        $this->actingAs(User::factory()->create())->get(route('logros.mios'))
            ->assertOk()->assertSee('Cuando juegues en un campeonato');
    }

    public function test_el_aviso_de_logros_nuevos_sale_una_sola_vez(): void
    {
        $this->actingAs($this->user)->get(route('logros.mios'))->assertSee('¡Tienes 3 logros nuevos!');
        $this->assertSame(0, Logro::whereNull('visto_en')->count());
        $this->actingAs($this->user)->get(route('logros.mios'))->assertDontSee('logros nuevos');

        // Uno solo: con su nombre
        Logro::where('clave', 'hat_trick')->update(['visto_en' => null]);
        $this->actingAs($this->user)->get(route('dashboard'))->assertSee('¡Desbloqueaste 🎩 Hat-trick!');
    }

    public function test_plantel_y_goleadores_muestran_insignias_y_enlazan_a_la_ficha(): void
    {
        $admin = User::factory()->create();
        $admin->ligas()->attach(1, ['rol' => 'admin']);

        $this->actingAs($admin)->get(route('equipos.show', $this->equipo))
            ->assertOk()->assertSee('title="Hat-trick"', false)->assertSee(route('jugadores.logros', $this->pedro), false);

        $this->actingAs($admin)->get(route('tablas.goleadores'))
            ->assertOk()->assertSee('title="Hat-trick"', false)->assertSee(route('jugadores.logros', $this->pedro), false);
    }
}
