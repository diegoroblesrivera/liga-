<?php

namespace Tests\Feature;

use App\Models\Pichanga;
use App\Models\PichangaFecha;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Requiere MySQL (las migraciones usan vistas, triggers y ALTER ... ENUM).
class PichangaTest extends TestCase
{
    use RefreshDatabase;

    protected function crearPichanga(User $organizador, array $datos = []): Pichanga
    {
        $this->actingAs($organizador)->post(route('pichangas.store'), $datos + [
            'nombre' => 'Pichanga martes',
            'dia_semana' => 2,
            'hora' => '21:00',
            'duracion_min' => 60,
            'cupo' => 2,
            'precio' => 30000,
        ])->assertRedirect();

        return Pichanga::latest('id')->firstOrFail();
    }

    public function test_cualquier_usuario_crea_una_pichanga_y_queda_como_organizador(): void
    {
        $user = User::factory()->create();
        $pichanga = $this->crearPichanga($user);

        $this->assertTrue($pichanga->esOrganizador($user));
        $this->actingAs($user)->get(route('pichangas.index'))->assertOk()->assertSee('Pichanga martes');
        $this->actingAs($user)->get(route('pichangas.show', $pichanga))->assertOk()->assertSee('Próxima fecha');
    }

    public function test_un_no_miembro_no_ve_la_pichanga_pero_puede_unirse_con_el_link(): void
    {
        $pichanga = $this->crearPichanga(User::factory()->create());
        $otro = User::factory()->create();

        $this->actingAs($otro)->get(route('pichangas.show', $pichanga))->assertForbidden();
        $this->actingAs($otro)->get(route('pichangas.unirse', $pichanga->token))->assertOk()->assertSee('Unirme');
        $this->actingAs($otro)->post(route('pichangas.unirse.store', $pichanga->token))
            ->assertRedirect(route('pichangas.show', $pichanga));

        $this->assertTrue($pichanga->esMiembro($otro));
        $this->assertFalse($pichanga->esOrganizador($otro));
    }

    public function test_cupo_lista_de_espera_y_ascenso_al_bajarse(): void
    {
        $org = User::factory()->create();
        $pichanga = $this->crearPichanga($org); // cupo 2
        [$b, $c] = User::factory()->count(2)->create();
        $pichanga->miembros()->attach([$b->id => ['rol' => 'jugador'], $c->id => ['rol' => 'jugador']]);

        $fecha = $pichanga->proximaFecha();
        $this->assertSame(2, $fecha->fecha->dayOfWeek);

        foreach ([$org, $b, $c] as $u) {
            $this->actingAs($u)->post(route('pichangas.fechas.voy', $fecha))->assertRedirect();
        }

        $this->assertEquals([$org->id, $b->id], $fecha->confirmados()->pluck('user_id')->all());
        $this->assertEquals([$c->id], $fecha->enEspera()->pluck('user_id')->all());

        // b se baja → c sube de la lista de espera
        $this->actingAs($b)->post(route('pichangas.fechas.no-voy', $fecha))->assertRedirect();
        $this->assertEquals([$org->id, $c->id], $fecha->confirmados()->pluck('user_id')->all());
        $this->assertSame(15000, $pichanga->precioPorPersona(2));
    }

    public function test_el_organizador_sortea_guarda_planilla_y_se_crea_la_fecha_siguiente(): void
    {
        $org = User::factory()->create();
        $pichanga = $this->crearPichanga($org);
        $b = User::factory()->create();
        $pichanga->miembros()->attach($b->id, ['rol' => 'jugador']);

        $fecha = $pichanga->proximaFecha();
        $fecha->anotar($org);
        $fecha->anotar($b);

        // Un jugador no puede tocar la planilla
        $this->actingAs($b)->post(route('pichangas.fechas.sortear', $fecha))->assertForbidden();

        $this->actingAs($org)->post(route('pichangas.fechas.sortear', $fecha))->assertRedirect();
        $this->assertEqualsCanonicalizing(['A', 'B'], $fecha->confirmados()->pluck('equipo')->all());

        [$a1, $a2] = $fecha->confirmados()->get();
        $this->actingAs($org)->put(route('pichangas.fechas.planilla', $fecha), [
            'jugadores' => [
                $a1->id => ['equipo' => 'A', 'pagado' => '1', 'goles' => 3],
                $a2->id => ['equipo' => 'B', 'goles' => 1],
            ],
            'goles_a' => 3,
            'goles_b' => 1,
            'jugada' => '1',
        ])->assertRedirect();

        $fecha->refresh();
        $this->assertSame('jugada', $fecha->estado);
        $this->assertTrue($a1->fresh()->pagado);
        $this->assertFalse($a2->fresh()->pagado);

        $siguiente = $pichanga->proximaFecha();
        $this->assertTrue($siguiente->fecha->equalTo($fecha->fecha->copy()->addWeek()));

        $this->actingAs($org)->get(route('pichangas.show', $pichanga))->assertOk()->assertSee('3 – 1');
        $this->actingAs($b)->get(route('pichangas.fechas.show', $fecha))->assertOk()->assertDontSee('Guardar planilla');
    }

    public function test_inscripciones_cerradas_no_permiten_anotarse(): void
    {
        $org = User::factory()->create();
        $pichanga = $this->crearPichanga($org);
        $fecha = $pichanga->proximaFecha();

        $this->actingAs($org)->post(route('pichangas.fechas.estado', $fecha), ['estado' => 'cerrada']);
        $this->actingAs($org)->post(route('pichangas.fechas.voy', $fecha))->assertSessionHasErrors('fecha');
        $this->assertSame(0, PichangaFecha::find($fecha->id)->asistencias()->count());
    }
}
