<?php

namespace Tests\Feature;

use App\Models\Pichanga;
use App\Models\User;
use App\Support\Run;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

// Requiere MySQL (las migraciones usan vistas, triggers y ALTER ... ENUM).
class DatosAccesoTest extends TestCase
{
    use RefreshDatabase;

    public function test_normaliza_y_valida_el_run(): void
    {
        $this->assertSame('12345678-5', Run::normalizar('12.345.678-5'));
        $this->assertSame('11111111-1', Run::normalizar(' 11111111 1 '));
        $this->assertSame('22222222-2', Run::normalizar('22222222-2'));
        $this->assertNull(Run::normalizar('12.345.678-9'));   // DV incorrecto
        $this->assertNull(Run::normalizar('123'));
        $this->assertSame('12.345.678-5', Run::formatear('12345678-5'));
        $this->assertSame('BBCL12', Run::normalizarPatente('bb-cl 12'));
    }

    public function test_guarda_run_patente_y_acompanantes_desde_el_perfil(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('profile.acceso.update'), [
            'run' => '12.345.678-5',
            'patente' => 'bb-cl 12',
            'acompanantes' => [
                ['nombre' => 'Javiera Carrera', 'run' => '11.111.111-1'],
                ['nombre' => '', 'run' => ''],                  // fila vacía: se ignora
                ['nombre' => 'Pedro Pérez', 'run' => '22222222-2'],
            ],
        ])->assertRedirect(route('profile.edit'))->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('12345678-5', $user->run);
        $this->assertSame('BBCL12', $user->patente);
        $this->assertEquals(['Javiera Carrera', 'Pedro Pérez'], $user->acompanantes->pluck('nombre')->all());

        // Volver a guardar reemplaza la lista
        $this->actingAs($user)->put(route('profile.acceso.update'), ['run' => '12345678-5', 'acompanantes' => []]);
        $this->assertSame(0, $user->acompanantes()->count());
        $this->assertNull($user->fresh()->patente);

        $this->actingAs($user)->get(route('profile.edit'))->assertOk()->assertSee('Datos para el acceso')->assertSee('12.345.678-5');
    }

    public function test_rechaza_run_invalido_y_acompanante_incompleto(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('profile.acceso.update'), [
            'run' => '12.345.678-9',
            'acompanantes' => [['nombre' => 'Sin RUN', 'run' => '']],
        ])->assertSessionHasErrorsIn('datosAcceso', ['run', 'acompanantes.0.run']);

        $this->assertNull($user->fresh()->run);
    }

    public function test_el_organizador_ve_la_lista_de_acceso_con_el_formato_pedido(): void
    {
        $org = User::factory()->create(['name' => 'Jugador uno', 'run' => '12345678-5', 'patente' => 'XXXX98']);
        $dos = User::factory()->create(['name' => 'Jugador dos', 'run' => '11111111-1']);
        $tres = User::factory()->create(['name' => 'Jugador 3']);
        $dos->acompanantes()->create(['nombre' => 'Javiera Carrera', 'run' => '22222222-2']);

        $pichanga = Pichanga::create([
            'nombre' => 'Martes', 'dia_semana' => 2, 'hora' => '21:00', 'cupo' => 10,
            'organizador_id' => $org->id, 'token' => Str::random(40),
        ]);
        $pichanga->miembros()->attach([
            $org->id => ['rol' => 'organizador'],
            $dos->id => ['rol' => 'jugador'],
            $tres->id => ['rol' => 'jugador'],
        ]);

        $fecha = $pichanga->proximaFecha();
        foreach ([$org, $dos, $tres] as $u) {
            $fecha->anotar($u);
            $this->travel(1)->seconds();
        }

        $esperado = implode("\n", [
            '1.- Jugador uno',
            'Run 12.345.678-5',
            'Patente XXXX98',
            '2.- Jugador dos',
            'Run 11.111.111-1',
            'Acompañantes',
            'Javiera Carrera',
            'Run 22.222.222-2',
            '3.- Jugador 3',
            'Run (sin registrar)',
        ]);
        $this->assertSame($esperado, $fecha->listaAcceso());

        $this->actingAs($org)->get(route('pichangas.fechas.show', $fecha))
            ->assertOk()
            ->assertSee('Lista de acceso')
            ->assertSee('3 jugadores + 1 acompañante')
            ->assertSee('Sin RUN registrado: Jugador 3');

        // Un jugador normal no ve RUN de nadie
        $this->actingAs($dos)->get(route('pichangas.fechas.show', $fecha))
            ->assertOk()
            ->assertDontSee('Lista de acceso')
            ->assertDontSee('12.345.678-5');
    }
}
