<?php

namespace Tests\Feature;

use App\Mail\InvitacionEquipo;
use App\Models\Equipo;
use App\Models\Invitacion;
use App\Models\Jugador;
use App\Models\Modalidad;
use App\Models\Role;
use App\Models\Torneo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

// Requiere MySQL (las migraciones usan vistas, triggers y ALTER ... ENUM).
class CargaMasivaTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected int $modalidad;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->admin = User::factory()->create(['name' => 'Organizador']);
        $this->admin->ligas()->attach(1, ['rol' => 'admin']);
        $this->modalidad = Modalidad::value('id');
    }

    protected function excel(array $filas): UploadedFile
    {
        $libro = new Spreadsheet;
        $libro->getActiveSheet()->fromArray($filas);
        $ruta = tempnam(sys_get_temp_dir(), 'carga').'.xlsx';
        (new Xlsx($libro))->save($ruta);

        return new UploadedFile($ruta, 'carga.xlsx', null, null, true);
    }

    protected function dt(Equipo $equipo): User
    {
        $dt = User::factory()->create(['equipo_id' => $equipo->id]);
        $dt->roles()->attach(Role::firstOrCreate(['nombre' => 'dt'])->id);

        return $dt;
    }

    public function test_el_organizador_carga_equipos_y_cada_dt_recibe_su_correo(): void
    {
        $this->actingAs($this->admin)->get(route('carga.equipos.plantilla'))->assertOk()->assertDownload('plantilla-equipos.xlsx');

        $archivo = $this->excel([
            ['Teléfono DT (opcional)', 'Equipo', 'Nombre DT', 'Email DT'], // columnas en otro orden
            ['+56 9 1111 1111', 'Leones', 'Juan Pérez', 'Juan@Correo.cl'],
            [null, 'Tigres', 'Ana Soto', 'ana@correo.cl'],
            [null, null, null, null], // fila vacía: se ignora
        ]);

        $this->actingAs($this->admin)
            ->post(route('carga.equipos.importar'), ['archivo' => $archivo, 'modalidad_id' => $this->modalidad])
            ->assertRedirect(route('equipos.index'))
            ->assertSessionHas('status', 'Se crearon 2 equipos. Se enviaron 2 invitaciones a sus DT.');

        $this->assertSame(['Leones', 'Tigres'], Equipo::orderBy('nombre')->pluck('nombre')->all());
        $inv = Invitacion::where('email', 'juan@correo.cl')->firstOrFail();
        $this->assertSame(['dt', 'Juan Pérez', '+56 9 1111 1111'], [$inv->tipo, $inv->nombre, $inv->telefono]);
        $this->assertNotNull($inv->enviada_en);
        Mail::assertSent(InvitacionEquipo::class, 2);
        Mail::assertSent(InvitacionEquipo::class, fn ($m) => $m->hasTo('juan@correo.cl') && $m->invitacion->is($inv));

        $correo = (new InvitacionEquipo($inv, 'Leones', 'Liga Demo', 'Organizador'))->render();
        $this->assertStringContainsString('Director Técnico de Leones', $correo);
        $this->assertStringContainsString(route('invitacion.show', $inv->token), $correo);
    }

    public function test_con_errores_no_se_carga_nada_y_se_listan_por_fila(): void
    {
        Equipo::forceCreate(['liga_id' => 1, 'nombre' => 'Leones', 'modalidad_id' => $this->modalidad]);

        $archivo = $this->excel([
            ['Equipo', 'Nombre DT', 'Email DT'],
            ['Pumas', 'Juan', 'juan@correo.cl'],
            ['leones', 'Ana', 'ana@correo.cl'],
            ['Pumas', 'Pía', 'no-es-un-correo'],
        ]);

        $this->actingAs($this->admin)
            ->post(route('carga.equipos.importar'), ['archivo' => $archivo, 'modalidad_id' => $this->modalidad])
            ->assertSessionHas('errores_filas', fn ($e) => array_keys($e) === [3, 4]
                && str_contains(implode(' ', $e[3]), 'Ya existe un equipo')
                && str_contains(implode(' ', $e[4]), 'repetido (fila 2)'));

        $this->assertSame(1, Equipo::count());
        Mail::assertNothingSent();

        // Sin las columnas obligatorias
        $this->actingAs($this->admin)
            ->post(route('carga.equipos.importar'), ['archivo' => $this->excel([['Club', 'Correo'], ['X', 'x@y.cl']]), 'modalidad_id' => $this->modalidad])
            ->assertSessionHasErrors(['archivo' => 'Faltan columnas en el archivo: Equipo y Nombre DT. Usa la plantilla.']);
    }

    public function test_respeta_el_limite_del_plan_gratis_y_es_solo_del_organizador(): void
    {
        foreach (range(1, 7) as $i) {
            Equipo::forceCreate(['liga_id' => 1, 'nombre' => "E{$i}", 'modalidad_id' => $this->modalidad]);
        }
        $archivo = $this->excel([['Equipo', 'Nombre DT', 'Email DT'], ['A', 'a', 'a@a.cl'], ['B', 'b', 'b@b.cl']]);

        $this->actingAs($this->admin)
            ->post(route('carga.equipos.importar'), ['archivo' => $archivo, 'modalidad_id' => $this->modalidad])
            ->assertSessionHasErrors('archivo');
        $this->assertSame(7, Equipo::count());

        $this->actingAs($this->dt(Equipo::first()))->get(route('carga.equipos'))->assertForbidden();
    }

    public function test_el_dt_carga_su_plantel_y_solo_los_que_tienen_email_reciben_correo(): void
    {
        $equipo = Equipo::forceCreate(['liga_id' => 1, 'nombre' => 'Leones', 'modalidad_id' => $this->modalidad]);
        $otro = Equipo::forceCreate(['liga_id' => 1, 'nombre' => 'Tigres', 'modalidad_id' => $this->modalidad]);
        $dt = $this->dt($equipo);

        $this->actingAs($dt)->get(route('carga.jugadores.plantilla', $equipo))->assertOk();
        $this->actingAs($dt)->get(route('carga.jugadores', $otro))->assertForbidden();

        // Dorsal repetido: no carga nada
        $malo = $this->excel([['Nombre', 'Dorsal', 'Posición', 'Email'], ['Pedro', 10, 'Delantero', 'pedro@correo.cl'], ['Luis', 10, null, null]]);
        $this->actingAs($dt)->post(route('carga.jugadores.importar', $equipo), ['archivo' => $malo])
            ->assertSessionHas('errores_filas', fn ($e) => str_contains(implode(' ', $e[3]), '#10 está repetida'));
        $this->assertSame(0, Jugador::count());

        $bueno = $this->excel([['Nombre', 'Dorsal', 'Posición', 'Email'], ['Pedro', 10, 'Delantero', 'pedro@correo.cl'], ['Luis', 1, 'Arquero', null]]);
        $this->actingAs($dt)->post(route('carga.jugadores.importar', $equipo), ['archivo' => $bueno])
            ->assertRedirect(route('equipos.show', $equipo))
            ->assertSessionHas('status', 'Se agregaron 2 jugadores al plantel y se enviaron 1 invitaciones por correo.');

        $pedro = Jugador::where('nombre', 'Pedro')->firstOrFail();
        $this->assertSame([10, 'Delantero', null], [$pedro->dorsal, $pedro->posicion, $pedro->user_id]);
        $this->assertSame($pedro->id, Invitacion::where('email', 'pedro@correo.cl')->value('jugador_id'));
        Mail::assertSent(InvitacionEquipo::class, 1);

        // La invitación pendiente se puede reenviar desde el equipo
        $this->actingAs($dt)->get(route('equipos.show', $equipo))->assertSee('Reenviar correo');
        $this->actingAs($dt)->post(route('invitaciones.reenviar', Invitacion::first()))->assertSessionHas('status');
        Mail::assertSent(InvitacionEquipo::class, 2);
    }

    public function test_el_jugador_cargado_crea_su_cuenta_y_reclama_su_ficha(): void
    {
        $equipo = Equipo::forceCreate(['liga_id' => 1, 'nombre' => 'Leones', 'modalidad_id' => $this->modalidad]);
        $ficha = Jugador::withoutGlobalScopes()->forceCreate(['equipo_id' => $equipo->id, 'nombre' => 'Pedro', 'dorsal' => 10, 'activo' => true]);
        $inv = Invitacion::forceCreate(['liga_id' => 1, 'equipo_id' => $equipo->id, 'jugador_id' => $ficha->id, 'tipo' => 'jugador', 'token' => str_repeat('a', 40), 'email' => 'pedro@correo.cl', 'nombre' => 'Pedro', 'reutilizable' => false]);

        $this->get(route('invitacion.show', $inv->token))->assertOk()
            ->assertSee('value="pedro@correo.cl"', false)->assertSee('#10')->assertSee('Continuar con Google');

        $this->post(route('invitacion.store', $inv->token), [
            'name' => 'Pedro González', 'email' => 'pedro@correo.cl', 'password' => 'clave-segura-123', 'password_confirmation' => 'clave-segura-123',
        ])->assertRedirect(route('jugadores.edit', $ficha->id));

        $user = User::where('email', 'pedro@correo.cl')->firstOrFail();
        $this->assertSame($user->id, $ficha->fresh()->user_id);
        $this->assertSame(1, Jugador::withoutGlobalScopes()->count(), 'No crea una ficha nueva');

        auth()->logout();
        $this->get(route('invitacion.show', $inv->token))->assertStatus(410);
    }

    public function test_un_dt_con_cuenta_acepta_la_invitacion_y_va_a_completar_su_equipo(): void
    {
        $equipo = Equipo::forceCreate(['liga_id' => 1, 'nombre' => 'Leones', 'modalidad_id' => $this->modalidad]);
        $inv = Invitacion::forceCreate(['liga_id' => 1, 'equipo_id' => $equipo->id, 'tipo' => 'dt', 'token' => str_repeat('b', 40), 'email' => 'juan@correo.cl', 'nombre' => 'Juan', 'telefono' => '+56 9 2222 2222', 'reutilizable' => false]);
        $juan = User::factory()->create(['email' => 'juan@correo.cl']);

        // Sin sesión: al iniciar sesión vuelve a la invitación
        $this->get(route('invitacion.show', $inv->token))->assertOk();
        $this->assertSame(route('invitacion.show', $inv->token), session('url.intended'));

        $this->actingAs($juan)->get(route('invitacion.show', $inv->token))->assertSee('Aceptar invitación');
        $this->actingAs($juan)->post(route('invitacion.aceptar', $inv->token))->assertRedirect(route('equipos.edit', $equipo));

        $juan->refresh();
        $this->assertSame($equipo->id, $juan->equipo_id);
        $this->assertSame('+56 9 2222 2222', $juan->telefono);
        $this->assertTrue($juan->hasRole('dt'));
        $this->actingAs($juan)->get(route('equipos.edit', $equipo))->assertOk()->assertSee('Completa sus datos');
    }

    public function test_la_exportacion_de_pagos_a_excel_funciona(): void
    {
        Torneo::forceCreate(['liga_id' => 1, 'nombre' => 'Apertura']);

        $this->actingAs($this->admin)->get(route('pagos.export.excel'))->assertOk()->assertDownload('pagos_apertura.xlsx');
    }
}
