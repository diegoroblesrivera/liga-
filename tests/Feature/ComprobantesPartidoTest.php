<?php

namespace Tests\Feature;

use App\Models\Equipo;
use App\Models\Modalidad;
use App\Models\PagoPartido;
use App\Models\Partido;
use App\Models\Role;
use App\Models\Torneo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// Requiere MySQL (las migraciones usan vistas, triggers y ALTER ... ENUM).
class ComprobantesPartidoTest extends TestCase
{
    use RefreshDatabase;

    protected Partido $partido;
    protected User $admin;
    protected User $dtLocal;
    protected User $dtVisita;

    protected function setUp(): void
    {
        parent::setUp();

        $modalidad = Modalidad::value('id');
        $local = Equipo::forceCreate(['liga_id' => 1, 'nombre' => 'Locales', 'modalidad_id' => $modalidad]);
        $visita = Equipo::forceCreate(['liga_id' => 1, 'nombre' => 'Visitas', 'modalidad_id' => $modalidad]);
        $torneo = Torneo::forceCreate(['liga_id' => 1, 'nombre' => 'Apertura']);
        $this->partido = Partido::withoutGlobalScopes()->forceCreate([
            'torneo_id' => $torneo->id, 'numero_fecha' => 1, 'equipo_local_id' => $local->id,
            'equipo_visita_id' => $visita->id, 'estado' => 'programado', 'monto_pago' => 20000,
        ]);

        $this->admin = User::factory()->create();
        $this->admin->ligas()->attach(1, ['rol' => 'admin']);

        $dt = Role::firstOrCreate(['nombre' => 'dt'])->id;
        $this->dtLocal = User::factory()->create(['equipo_id' => $local->id]);
        $this->dtLocal->roles()->attach($dt);
        $this->dtVisita = User::factory()->create(['equipo_id' => $visita->id]);
        $this->dtVisita->roles()->attach($dt);
    }

    public function test_el_comprobante_se_guarda_en_privado_y_solo_lo_ven_el_admin_y_su_dt(): void
    {
        Storage::fake('local');

        $this->actingAs($this->dtLocal)->post(route('pagos.comprobante.store', $this->partido), [
            'comprobante' => UploadedFile::fake()->image('transferencia.png'),
        ])->assertRedirect(route('pagos.mis-pagos'));

        $pago = PagoPartido::withoutGlobalScopes()->firstOrFail();
        $this->assertStringStartsWith('comprobantes-partidos/'.$this->partido->id.'/', $pago->comprobante_path);
        Storage::disk('local')->assertExists($pago->comprobante_path);
        $this->assertFileDoesNotExist(public_path($pago->comprobante_path));

        $this->actingAs($this->dtLocal)->get(route('pagos.comprobante.ver', $pago))->assertOk();
        $this->actingAs($this->admin)->get(route('pagos.comprobante.ver', $pago))->assertOk();
        $this->actingAs($this->dtVisita)->get(route('pagos.comprobante.ver', $pago))->assertForbidden();

        auth()->logout();
        $this->get(route('pagos.comprobante.ver', $pago))->assertRedirect(route('login'));

        // El admin ve el enlace protegido en la ficha del partido, no una ruta pública
        $this->actingAs($this->admin)->get(route('partidos.show', $this->partido))
            ->assertSee(route('pagos.comprobante.ver', $pago), false)
            ->assertDontSee('uploads/comprobantes', false);
    }

    public function test_reemplazar_el_comprobante_borra_el_anterior(): void
    {
        Storage::fake('local');

        $this->actingAs($this->dtLocal)->post(route('pagos.comprobante.store', $this->partido), ['comprobante' => UploadedFile::fake()->image('uno.png')]);
        $primero = PagoPartido::withoutGlobalScopes()->value('comprobante_path');
        $this->actingAs($this->dtLocal)->post(route('pagos.comprobante.store', $this->partido), ['comprobante' => UploadedFile::fake()->image('dos.png')]);

        Storage::disk('local')->assertMissing($primero);
        Storage::disk('local')->assertExists(PagoPartido::withoutGlobalScopes()->value('comprobante_path'));
    }

    public function test_la_migracion_mueve_los_comprobantes_publicos_a_privado(): void
    {
        Storage::fake('local');

        $viejo = 'uploads/comprobantes/'.$this->partido->id.'/comp_'.$this->partido->id.'_9_123.jpg';
        File::ensureDirectoryExists(dirname(public_path($viejo)));
        File::put(public_path($viejo), 'imagen');
        $id = DB::table('pagos_partidos')->insertGetId([
            'partido_id' => $this->partido->id, 'equipo_id' => $this->partido->equipo_local_id,
            'comprobante_path' => $viejo, 'fecha_subida' => now(),
        ]);

        (require database_path('migrations/2026_10_18_000001_mover_comprobantes_de_partidos_a_privado.php'))->up();

        $nuevo = DB::table('pagos_partidos')->where('id', $id)->value('comprobante_path');
        $this->assertSame('comprobantes-partidos/'.$this->partido->id.'/comp_'.$this->partido->id.'_9_123.jpg', $nuevo);
        Storage::disk('local')->assertExists($nuevo);
        $this->assertSame('imagen', Storage::disk('local')->get($nuevo));
        $this->assertFileDoesNotExist(public_path($viejo));
    }
}
