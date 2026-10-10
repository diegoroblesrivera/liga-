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
            'pago_modo' => 'completa',
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

    public function test_el_precio_es_libre_pero_mayor_que_cero(): void
    {
        $pichanga = $this->crearPichanga(User::factory()->create(), ['precio' => 2900]);
        $this->assertSame(2900, $pichanga->precio);

        $this->actingAs(User::factory()->create())->post(route('pichangas.store'), [
            'nombre' => 'Gratis', 'dia_semana' => 2, 'hora' => '21:00',
            'duracion_min' => 60, 'cupo' => 10, 'precio' => 0,
        ])->assertSessionHasErrors('precio');
    }

    public function test_el_valor_por_persona_se_redondea_hacia_arriba_a_multiplos_de_50(): void
    {
        $p = new Pichanga(['pago_modo' => 'completa']);

        foreach ([
            [27890, 10, 2800],  // 2.789 → 2.800
            [27140, 10, 2750],  // 2.714 → 2.750
            [27000, 10, 2700],  // exacto, no cambia
            [27001, 10, 2750],  // 2.700,1 → 2.750
            [2900, 3, 1000],    // 966,67 → 1.000
        ] as [$total, $personas, $esperado]) {
            $p->precio = $total;
            $this->assertSame($esperado, $p->precioPorPersona($personas), "{$total} / {$personas}");
        }

        $this->assertNull($p->precioPorPersona(0));
    }

    public function test_por_defecto_cupo_14_sin_rival_y_cancha_completa(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('pichangas.create'))
            ->assertOk()
            ->assertSee('value="14"', false)
            ->assertSee('Existe equipo rival');
    }

    public function test_con_rival_paga_la_mitad_y_se_divide_entre_confirmados(): void
    {
        $org = User::factory()->create();
        $pichanga = $this->crearPichanga($org, [
            'cupo' => 14,
            'precio' => 29000,
            'tiene_rival' => '1',
            'rival_nombre' => 'Los del jueves',
            'pago_modo' => 'mitad',
        ]);

        $this->assertTrue($pichanga->tiene_rival);
        $this->assertSame('Los del jueves', $pichanga->nombreRival());
        $this->assertSame(14500, $pichanga->totalAPagar());
        $this->assertSame(1450, $pichanga->precioPorPersona(10));

        $fecha = $pichanga->proximaFecha();
        $fecha->anotar($org);

        $this->actingAs($org)->get(route('pichangas.show', $pichanga))->assertOk()->assertSee('vs Los del jueves');
        $this->actingAs($org)->get(route('pichangas.fechas.show', $fecha))
            ->assertOk()
            ->assertSee('Nosotros')
            ->assertSee('Goles Los del jueves')
            ->assertDontSee('Sortear equipos');

        // Al quitar el rival se borra el nombre y vuelve a la cancha completa
        $this->actingAs($org)->put(route('pichangas.update', $pichanga), [
            'nombre' => 'Pichanga martes', 'dia_semana' => 2, 'hora' => '21:00', 'duracion_min' => 60,
            'cupo' => 14, 'precio' => 29000, 'tiene_rival' => '0', 'rival_nombre' => 'Los del jueves',
            'pago_modo' => 'completa', 'activa' => '1',
        ])->assertSessionHasNoErrors();

        $pichanga->refresh();
        $this->assertFalse($pichanga->tiene_rival);
        $this->assertNull($pichanga->rival_nombre);
        $this->assertSame(29000, $pichanga->totalAPagar());
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

    public function test_a_las_22_hora_chile_la_fecha_de_hoy_sigue_siendo_la_proxima(): void
    {
        // Martes 13-10-2026 a las 22:30 en Chile (ya es miércoles en UTC).
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-13 22:30', 'America/Santiago'));

        $pichanga = $this->crearPichanga(User::factory()->create()); // martes 21:00
        $hoy = $pichanga->proximaFecha();

        $this->assertSame('2026-10-13', $hoy->fecha->toDateString());
        $this->assertTrue($pichanga->proximaFecha()->is($hoy));
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
