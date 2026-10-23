<?php

namespace Tests\Feature;

use App\Models\Pichanga;
use App\Models\PichangaFecha;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

// Requiere MySQL (las migraciones usan vistas, triggers y ALTER ... ENUM).
class PichangaPagoTest extends TestCase
{
    use RefreshDatabase;

    protected User $org;
    protected User $jugador;
    protected Pichanga $pichanga;
    protected PichangaFecha $fecha;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->org = User::factory()->create();
        $this->jugador = User::factory()->create();

        $this->pichanga = Pichanga::create([
            'nombre' => 'Martes', 'dia_semana' => 2, 'hora' => '21:00', 'cupo' => 14,
            'precio' => 28000, 'pago_modo' => 'completa',
            'datos_transferencia' => "Juan Pérez\nBanco Estado\nCuenta RUT 12345678",
            'organizador_id' => $this->org->id, 'token' => Str::random(40),
        ]);
        $this->pichanga->miembros()->attach([
            $this->org->id => ['rol' => 'organizador'],
            $this->jugador->id => ['rol' => 'jugador'],
        ]);

        $this->fecha = $this->pichanga->proximaFecha();
        $this->fecha->anotar($this->org);
        $this->fecha->anotar($this->jugador);
    }

    public function test_el_jugador_ve_su_monto_y_los_datos_para_transferir(): void
    {
        $this->actingAs($this->jugador)->get(route('pichangas.show', $this->pichanga))
            ->assertOk()
            ->assertSee('Tu pago: $14.000')
            ->assertSee('Pendiente')
            ->assertSee('Banco Estado')
            ->assertSee('Subir comprobante');
    }

    public function test_subir_comprobante_lo_deja_pagado_y_guarda_la_imagen_en_privado(): void
    {
        $this->actingAs($this->jugador)
            ->post(route('pichangas.comprobante.store', $this->fecha), [
                'comprobante' => UploadedFile::fake()->image('transferencia.png'),
            ])->assertRedirect()->assertSessionHasNoErrors();

        $asistencia = $this->fecha->asistencias()->where('user_id', $this->jugador->id)->first();
        $this->assertTrue($asistencia->pagado);
        $this->assertNotNull($asistencia->comprobante_subido_en);
        $this->assertStringStartsWith('comprobantes-pichangas/'.$this->fecha->id.'/', $asistencia->comprobante_path);
        Storage::disk('local')->assertExists($asistencia->comprobante_path);
        $this->assertFileDoesNotExist(public_path($asistencia->comprobante_path));

        // El dueño y el organizador pueden verla; otro miembro no
        $this->actingAs($this->jugador)->get(route('pichangas.comprobante.show', $asistencia))->assertOk();
        $this->actingAs($this->org)->get(route('pichangas.comprobante.show', $asistencia))->assertOk();
        $otro = User::factory()->create();
        $this->pichanga->miembros()->attach($otro->id, ['rol' => 'jugador']);
        $this->actingAs($otro)->get(route('pichangas.comprobante.show', $asistencia))->assertForbidden();

        // El organizador ve el enlace en la planilla
        $this->actingAs($this->org)->get(route('pichangas.fechas.show', $this->fecha))
            ->assertOk()
            ->assertSee(route('pichangas.comprobante.show', $asistencia), false);
    }

    public function test_reemplazar_comprobante_borra_el_anterior(): void
    {
        $this->actingAs($this->jugador)->post(route('pichangas.comprobante.store', $this->fecha), [
            'comprobante' => UploadedFile::fake()->image('uno.png'),
        ]);
        $primero = $this->fecha->asistencias()->where('user_id', $this->jugador->id)->value('comprobante_path');

        $this->actingAs($this->jugador)->post(route('pichangas.comprobante.store', $this->fecha), [
            'comprobante' => UploadedFile::fake()->image('dos.png'),
        ]);
        $segundo = $this->fecha->asistencias()->where('user_id', $this->jugador->id)->value('comprobante_path');

        $this->assertNotSame($primero, $segundo);
        Storage::disk('local')->assertMissing($primero);
        Storage::disk('local')->assertExists($segundo);
    }

    public function test_solo_confirmados_y_solo_imagenes(): void
    {
        $noVa = User::factory()->create();
        $this->pichanga->miembros()->attach($noVa->id, ['rol' => 'jugador']);

        $this->actingAs($noVa)->post(route('pichangas.comprobante.store', $this->fecha), [
            'comprobante' => UploadedFile::fake()->image('x.png'),
        ])->assertSessionHasErrors('comprobante');

        $this->actingAs($this->jugador)->post(route('pichangas.comprobante.store', $this->fecha), [
            'comprobante' => UploadedFile::fake()->create('virus.php', 10, 'application/x-php'),
        ])->assertSessionHasErrors('comprobante');

        $this->assertFalse((bool) $this->fecha->asistencias()->where('user_id', $this->jugador->id)->value('pagado'));
    }

    public function test_solo_el_organizador_borra_la_pichanga_con_todo_y_comprobantes(): void
    {
        $this->actingAs($this->jugador)->post(route('pichangas.comprobante.store', $this->fecha), [
            'comprobante' => UploadedFile::fake()->image('t.png'),
        ]);
        $ruta = $this->fecha->asistencias()->where('user_id', $this->jugador->id)->value('comprobante_path');
        Storage::disk('local')->assertExists($ruta);

        // Un jugador no puede, y no ve el botón
        $this->actingAs($this->jugador)->get(route('pichangas.show', $this->pichanga))->assertDontSee('Borrar pichanga');
        $this->actingAs($this->jugador)->delete(route('pichangas.destroy', $this->pichanga))->assertForbidden();

        // El organizador ve el botón con la advertencia y la borra
        $this->actingAs($this->org)->get(route('pichangas.show', $this->pichanga))
            ->assertSee('Borrar pichanga')
            ->assertSee('NO se puede recuperar');
        $this->actingAs($this->org)->get(route('pichangas.edit', $this->pichanga))->assertSee('No se puede recuperar.');

        $this->actingAs($this->org)->delete(route('pichangas.destroy', $this->pichanga))
            ->assertRedirect(route('pichangas.index'))
            ->assertSessionHas('status', 'La pichanga "Martes" fue borrada.');

        $this->assertNull(Pichanga::find($this->pichanga->id));
        $this->assertSame(0, PichangaFecha::where('pichanga_id', $this->pichanga->id)->count());
        $this->assertSame(0, \App\Models\PichangaAsistencia::where('pichanga_fecha_id', $this->fecha->id)->count());
        Storage::disk('local')->assertMissing($ruta);
    }

    public function test_el_organizador_sigue_pudiendo_marcar_pagos_presenciales(): void
    {
        $asistencia = $this->fecha->asistencias()->where('user_id', $this->jugador->id)->first();

        $this->actingAs($this->org)->put(route('pichangas.fechas.planilla', $this->fecha), [
            'jugadores' => [$asistencia->id => ['pagado' => '1', 'goles' => 0]],
        ])->assertSessionHasNoErrors();

        $this->assertTrue($asistencia->fresh()->pagado);
        $this->assertNull($asistencia->fresh()->comprobante_path);

        $this->actingAs($this->jugador)->get(route('pichangas.show', $this->pichanga))
            ->assertSee('✓ Pagado')
            ->assertSee('marcado por el organizador');
    }

    public function test_dividir_la_cancha_reparte_el_total_entre_los_confirmados(): void
    {
        // 28.000 / 2 confirmados
        $this->assertSame(14000, $this->pichanga->precioPorPersona(2));
        $this->actingAs($this->org)->get(route('pichangas.fechas.show', $this->fecha))->assertOk()->assertSeeText('÷ 2 = $14.000 por persona');
    }

    public function test_cuota_fija_cobra_lo_mismo_sin_importar_cuantos_se_anoten(): void
    {
        $datos = ['nombre' => 'Martes', 'dia_semana' => 2, 'hora' => '21:00', 'duracion_min' => 60, 'cupo' => 14,
            'pago_modo' => 'completa', 'cobro' => 'cuota', 'cuota' => 3500, 'precio' => 28000, 'activa' => 1];

        $this->actingAs($this->org)->put(route('pichangas.update', $this->pichanga), $datos)->assertSessionHasNoErrors();
        $p = $this->pichanga->fresh();
        $this->assertSame(['cuota', 3500, null], [$p->cobro, $p->cuota, $p->precio], 'Guarda solo el monto del modo elegido');
        $this->assertSame(3500, $p->precioPorPersona(2));
        $this->assertSame(3500, $p->precioPorPersona(11));

        $this->actingAs($this->org)->get(route('pichangas.fechas.show', $this->fecha))->assertOk()
            ->assertSeeText('Cuota fija: $3.500 por persona')->assertSeeText('se recaudan $7.000');
        $this->actingAs($this->jugador)->get(route('pichangas.show', $this->pichanga))->assertOk()->assertSee('Tu pago: $3.500', false);

        // Sin monto de cuota no se puede guardar
        $this->actingAs($this->org)->put(route('pichangas.update', $this->pichanga), ['cuota' => null] + $datos)->assertSessionHasErrors('cuota');

        // Volver a dividir la cancha
        $this->actingAs($this->org)->put(route('pichangas.update', $this->pichanga), ['cobro' => 'dividido', 'cuota' => null, 'precio' => 30000] + $datos)->assertSessionHasNoErrors();
        $this->assertSame(15000, $this->pichanga->fresh()->precioPorPersona(2));
    }

    public function test_el_formulario_muestra_las_dos_opciones_de_cobro(): void
    {
        $this->actingAs($this->org)->get(route('pichangas.edit', $this->pichanga))->assertOk()
            ->assertSee('Dividir la cancha')->assertSee('Cuota fija por persona')->assertSee('name="cobro"', false);
        $this->actingAs($this->org)->get(route('pichangas.create'))->assertOk()->assertSee('Cuota fija por persona');
    }
}
