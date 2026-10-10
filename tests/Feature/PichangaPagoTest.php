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
}
