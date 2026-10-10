<?php

namespace Tests\Feature;

use App\Models\Pichanga;
use App\Models\PichangaAsistencia;
use App\Models\PichangaFecha;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

// Requiere MySQL (las migraciones usan vistas, triggers y ALTER ... ENUM).
class PichangaInvitadosTest extends TestCase
{
    use RefreshDatabase;

    protected User $org;
    protected User $diego;
    protected Pichanga $pichanga;
    protected PichangaFecha $fecha;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->org = User::factory()->create(['name' => 'Orga']);
        $this->diego = User::factory()->create(['name' => 'DiegoR']);

        $this->pichanga = Pichanga::create([
            'nombre' => 'Martes', 'dia_semana' => 2, 'hora' => '21:00', 'cupo' => 2,
            'precio' => 30000, 'pago_modo' => 'completa',
            'organizador_id' => $this->org->id, 'token' => Str::random(40),
        ]);
        $this->pichanga->miembros()->attach([
            $this->org->id => ['rol' => 'organizador'],
            $this->diego->id => ['rol' => 'jugador'],
        ]);

        $this->fecha = $this->pichanga->proximaFecha();
        $this->fecha->anotar($this->org);
        $this->fecha->anotar($this->diego);
    }

    protected function invitar(User $anfitrion, array $datos = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($anfitrion)->post(route('pichangas.invitados.store', $this->fecha), $datos);
    }

    public function test_un_invitado_sin_nombre_aparece_como_jugador_nuevo_y_no_ocupa_cupo(): void
    {
        $this->invitar($this->diego)->assertSessionHasNoErrors();

        $invitado = $this->fecha->asistencias()->whereNotNull('invitado_por')->firstOrFail();
        $this->assertSame('Jugador nuevo (DiegoR)', $invitado->nombreVisible());
        $this->assertNull($invitado->user_id);
        $this->assertSame('confirmado', $invitado->estado);

        // Cupo 2 ocupado por Orga y DiegoR: el invitado va aparte
        $this->assertSame(2, $this->fecha->cuposOcupados());

        $this->actingAs($this->diego)->get(route('pichangas.show', $this->pichanga))
            ->assertOk()
            ->assertSee('2/2 confirmados')
            ->assertSee('+ 1 invitado')
            ->assertSee('Jugador nuevo (DiegoR)')
            ->assertSee('Tu pago: $10.000'); // 30.000 entre 3 que juegan
    }

    public function test_invitado_con_nombre_y_run_va_a_la_lista_de_acceso(): void
    {
        $this->invitar($this->diego, ['invitado_nombre' => 'Juan Pérez', 'invitado_run' => '11.111.111-1'])
            ->assertSessionHasNoErrors();

        $this->assertStringContainsString(
            "3.- Juan Pérez (invitado de DiegoR)\nRun 11.111.111-1",
            $this->fecha->fresh()->listaAcceso()
        );

        $this->invitar($this->diego, ['invitado_run' => '12.345.678-9'])->assertSessionHasErrors('invitado_run');
    }

    public function test_reglas_para_llevar_invitados(): void
    {
        // Solo confirmados
        $otro = User::factory()->create();
        $this->pichanga->miembros()->attach($otro->id, ['rol' => 'jugador']);
        $this->invitar($otro)->assertSessionHasErrors('invitado');

        // Máximo 5
        foreach (range(1, PichangaFecha::MAX_INVITADOS_POR_JUGADOR) as $i) {
            $this->invitar($this->diego)->assertSessionHasNoErrors();
        }
        $this->invitar($this->diego)->assertSessionHasErrors('invitado');

        // Con inscripciones cerradas, no
        $this->fecha->update(['estado' => 'cerrada']);
        $this->invitar($this->org)->assertSessionHasErrors('invitado');
    }

    public function test_si_el_anfitrion_no_va_sus_invitados_tambien_salen(): void
    {
        $this->invitar($this->diego);
        $this->invitar($this->diego);

        $this->actingAs($this->diego)->post(route('pichangas.fechas.no-voy', $this->fecha));

        $this->assertSame(0, $this->fecha->invitadosDe($this->diego)->count());
    }

    public function test_quitar_un_invitado(): void
    {
        $this->invitar($this->diego);
        $invitado = $this->fecha->asistencias()->whereNotNull('invitado_por')->firstOrFail();

        $otro = User::factory()->create();
        $this->pichanga->miembros()->attach($otro->id, ['rol' => 'jugador']);
        $this->actingAs($otro)->delete(route('pichangas.invitados.destroy', $invitado))->assertForbidden();

        $this->actingAs($this->org)->delete(route('pichangas.invitados.destroy', $invitado))->assertRedirect();
        $this->assertNull(PichangaAsistencia::find($invitado->id));

        // No se puede "quitar" a un jugador con cuenta por esta vía
        $asistenciaOrg = $this->fecha->asistencias()->where('user_id', $this->org->id)->first();
        $this->actingAs($this->org)->delete(route('pichangas.invitados.destroy', $asistenciaOrg))->assertNotFound();
    }

    public function test_pagar_por_sus_invitados_y_por_otro_jugador(): void
    {
        $this->invitar($this->diego, ['invitado_nombre' => 'Juan']);
        $invitado = $this->fecha->asistencias()->whereNotNull('invitado_por')->firstOrFail();
        $asistenciaOrg = $this->fecha->asistencias()->where('user_id', $this->org->id)->firstOrFail();

        // Una asistencia de otra fecha: debe ignorarse
        $otraFecha = $this->pichanga->fechas()->create(['fecha' => now()->addWeeks(3)->toDateString(), 'hora' => '21:00']);
        $ajena = $otraFecha->anotar($this->org);

        $this->actingAs($this->diego)->get(route('pichangas.show', $this->pichanga))
            ->assertSee('Pago también por')
            ->assertSee('Juan (invitado de DiegoR)');

        $this->actingAs($this->diego)->post(route('pichangas.comprobante.store', $this->fecha), [
            'comprobante' => UploadedFile::fake()->image('transferencia.png'),
            'pago_por' => [$invitado->id, $asistenciaOrg->id, $ajena->id],
        ])->assertSessionHas('status', '¡Comprobante subido! Quedaron pagados tú y 2 personas más.');

        $mia = $this->fecha->asistencias()->where('user_id', $this->diego->id)->first();
        foreach ([$mia, $invitado->fresh(), $asistenciaOrg->fresh()] as $a) {
            $this->assertTrue($a->pagado);
            $this->assertSame($mia->comprobante_path, $a->comprobante_path);
        }
        $this->assertNull($mia->pagado_por);
        $this->assertSame($this->diego->id, $invitado->fresh()->pagado_por);
        $this->assertSame($this->diego->id, $asistenciaOrg->fresh()->pagado_por);
        $this->assertFalse($ajena->fresh()->pagado);

        // Orga ve que pagó DiegoR y puede abrir el comprobante; DiegoR puede abrir el de su invitado
        $this->actingAs($this->org)->get(route('pichangas.show', $this->pichanga))->assertSee('pagó DiegoR');
        $this->actingAs($this->org)->get(route('pichangas.comprobante.show', $asistenciaOrg))->assertOk();
        $this->actingAs($this->diego)->get(route('pichangas.comprobante.show', $invitado))->assertOk();
    }

    public function test_no_se_paga_por_quien_ya_pago_y_el_comprobante_compartido_no_se_borra(): void
    {
        // Orga ya pagó por su cuenta
        $this->actingAs($this->org)->post(route('pichangas.comprobante.store', $this->fecha), [
            'comprobante' => UploadedFile::fake()->image('orga.png'),
        ]);
        $asistenciaOrg = $this->fecha->asistencias()->where('user_id', $this->org->id)->first();
        $rutaOrga = $asistenciaOrg->comprobante_path;

        // DiegoR intenta pagar por Orga: se ignora
        $this->actingAs($this->diego)->post(route('pichangas.comprobante.store', $this->fecha), [
            'comprobante' => UploadedFile::fake()->image('diego.png'),
            'pago_por' => [$asistenciaOrg->id],
        ])->assertSessionHas('status', '¡Comprobante subido! Quedaste como pagado.');
        $this->assertSame($rutaOrga, $asistenciaOrg->fresh()->comprobante_path);
        $this->assertNull($asistenciaOrg->fresh()->pagado_por);

        // DiegoR paga por su invitado y luego reemplaza su comprobante: el viejo
        // se borra solo cuando ya nadie lo usa
        $this->invitar($this->diego);
        $invitado = $this->fecha->asistencias()->whereNotNull('invitado_por')->first();
        $this->actingAs($this->diego)->post(route('pichangas.comprobante.store', $this->fecha), [
            'comprobante' => UploadedFile::fake()->image('ambos.png'),
            'pago_por' => [$invitado->id],
        ]);
        $compartido = $invitado->fresh()->comprobante_path;

        $this->actingAs($this->diego)->post(route('pichangas.comprobante.store', $this->fecha), [
            'comprobante' => UploadedFile::fake()->image('solo-yo.png'),
        ]);
        Storage::disk('local')->assertExists($compartido); // aún lo usa el invitado
        Storage::disk('local')->assertExists($rutaOrga);
    }

    public function test_los_invitados_no_suman_al_ranking(): void
    {
        $this->invitar($this->diego);
        $invitado = $this->fecha->asistencias()->whereNotNull('invitado_por')->first();
        $invitado->update(['goles' => 5]);
        $this->fecha->update(['estado' => 'jugada']);

        $this->actingAs($this->org)->get(route('pichangas.show', $this->pichanga))
            ->assertOk()
            ->assertDontSee('Jugador nuevo (DiegoR)</td>', false);
    }
}
