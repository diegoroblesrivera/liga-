<?php

namespace Tests\Feature;

use App\Models\Equipo;
use App\Models\Gol;
use App\Models\Jugador;
use App\Models\Liga;
use App\Models\Modalidad;
use App\Models\PagoPartido;
use App\Models\Partido;
use App\Models\Torneo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// Requiere MySQL (las migraciones usan vistas, triggers y ALTER ... ENUM).
class BorrarPartidosTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Torneo $torneo;
    protected Equipo $local;
    protected Equipo $visita;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->admin->ligas()->attach(1, ['rol' => 'admin']);

        $modalidad = Modalidad::value('id');
        $this->local = Equipo::forceCreate(['liga_id' => 1, 'nombre' => 'Locales', 'modalidad_id' => $modalidad]);
        $this->visita = Equipo::forceCreate(['liga_id' => 1, 'nombre' => 'Visitas', 'modalidad_id' => $modalidad]);
        $this->torneo = Torneo::forceCreate(['liga_id' => 1, 'nombre' => 'Apertura']);
    }

    protected function partido(int $fecha, string $estado = 'programado', ?Torneo $torneo = null): Partido
    {
        return Partido::withoutGlobalScopes()->forceCreate([
            'torneo_id' => ($torneo ?? $this->torneo)->id, 'numero_fecha' => $fecha,
            'equipo_local_id' => $this->local->id, 'equipo_visita_id' => $this->visita->id,
            'estado' => $estado, 'goles_local' => $estado === 'finalizado' ? 1 : null, 'goles_visita' => $estado === 'finalizado' ? 0 : null,
        ]);
    }

    public function test_el_admin_elimina_un_partido_con_sus_goles_pagos_y_comprobantes(): void
    {
        $partido = $this->partido(1, 'finalizado');
        $jugador = Jugador::forceCreate(['equipo_id' => $this->local->id, 'nombre' => 'Goleador']);
        Gol::forceCreate(['partido_id' => $partido->id, 'jugador_id' => $jugador->id, 'equipo_id' => $this->local->id, 'minuto' => 10, 'tipo' => 'normal']);

        Storage::fake('local');
        $ruta = 'comprobantes-partidos/'.$partido->id.'/comp.jpg';
        Storage::disk('local')->put($ruta, 'x');
        PagoPartido::forceCreate(['partido_id' => $partido->id, 'equipo_id' => $this->local->id, 'comprobante_path' => $ruta]);

        $this->actingAs($this->admin)->get(route('partidos.show', $partido))
            ->assertOk()->assertSee('Eliminar')->assertSee('cambia la tabla de posiciones');

        $this->actingAs($this->admin)->delete(route('partidos.destroy', $partido))
            ->assertRedirect(route('partidos.index'))
            ->assertSessionHas('status', 'Partido eliminado: Locales vs Visitas.');

        $this->assertNull(Partido::withoutGlobalScopes()->find($partido->id));
        $this->assertSame(0, Gol::withoutGlobalScopes()->where('partido_id', $partido->id)->count());
        $this->assertSame(0, PagoPartido::withoutGlobalScopes()->where('partido_id', $partido->id)->count());
        Storage::disk('local')->assertMissing($ruta);
    }

    public function test_el_admin_elimina_una_fecha_completa_solo_de_ese_torneo(): void
    {
        $f2a = $this->partido(2);
        $f2b = $this->partido(2, 'finalizado');
        $f3 = $this->partido(3);
        $otroTorneo = Torneo::forceCreate(['liga_id' => 1, 'nombre' => 'Clausura']);
        $f2OtroTorneo = $this->partido(2, 'programado', $otroTorneo);

        $this->actingAs($this->admin)->get(route('partidos.index'))
            ->assertOk()->assertSee('Eliminar fecha 2');

        $this->actingAs($this->admin)->delete(route('partidos.fechas.destroy', [$this->torneo, 2]))
            ->assertRedirect(route('partidos.index'))
            ->assertSessionHas('status', 'Fecha 2 de "Apertura" eliminada (2 partidos).');

        $quedan = Partido::withoutGlobalScopes()->pluck('id')->all();
        $this->assertNotContains($f2a->id, $quedan);
        $this->assertNotContains($f2b->id, $quedan);
        $this->assertContains($f3->id, $quedan);
        $this->assertContains($f2OtroTorneo->id, $quedan);

        // Una fecha que no existe
        $this->actingAs($this->admin)->delete(route('partidos.fechas.destroy', [$this->torneo, 9]))->assertNotFound();
    }

    public function test_solo_el_admin_del_campeonato_puede_eliminar(): void
    {
        $partido = $this->partido(1);

        // Un DT del campeonato no puede
        $dt = User::factory()->create(['equipo_id' => $this->local->id]);
        $dt->roles()->attach(\App\Models\Role::firstOrCreate(['nombre' => 'dt'])->id);
        $this->actingAs($dt)->get(route('partidos.show', $partido))->assertOk()->assertDontSee('¿Eliminar este partido?');
        $this->actingAs($dt)->delete(route('partidos.destroy', $partido))->assertForbidden();
        $this->actingAs($dt)->delete(route('partidos.fechas.destroy', [$this->torneo, 1]))->assertForbidden();

        // El admin de OTRO campeonato ni siquiera lo encuentra
        $otro = User::factory()->create();
        $ligaB = Liga::create(['nombre' => 'Liga B', 'slug' => 'liga-b', 'owner_id' => $otro->id, 'activa' => true, 'publica' => false]);
        $otro->ligas()->attach($ligaB->id, ['rol' => 'owner']);
        $this->actingAs($otro)->delete(route('partidos.destroy', $partido))->assertNotFound();
        $this->actingAs($otro)->delete(route('partidos.fechas.destroy', [$this->torneo, 1]))->assertNotFound();

        $this->assertNotNull(Partido::withoutGlobalScopes()->find($partido->id));
    }
}
