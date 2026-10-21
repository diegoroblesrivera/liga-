<?php

namespace Tests\Feature;

use App\Models\Equipo;
use App\Models\Invitacion;
use App\Models\Jugador;
use App\Models\Liga;
use App\Models\Modalidad;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

// Requiere MySQL (las migraciones usan vistas, triggers y ALTER ... ENUM).
class UnEquipoPorCampeonatoTest extends TestCase
{
    use RefreshDatabase;

    protected Equipo $leones;
    protected Equipo $tigres;
    protected User $pedro;

    protected function setUp(): void
    {
        parent::setUp();

        $m = Modalidad::value('id');
        $this->leones = Equipo::forceCreate(['liga_id' => 1, 'nombre' => 'Leones', 'modalidad_id' => $m]);
        $this->tigres = Equipo::forceCreate(['liga_id' => 1, 'nombre' => 'Tigres', 'modalidad_id' => $m]);
        $this->pedro = User::factory()->create(['email' => 'pedro@correo.cl']);
        Jugador::withoutGlobalScopes()->forceCreate(['equipo_id' => $this->leones->id, 'user_id' => $this->pedro->id, 'nombre' => 'Pedro', 'activo' => true]);
    }

    protected function invitacion(Equipo $equipo, int $ligaId = 1): Invitacion
    {
        return Invitacion::forceCreate(['liga_id' => $ligaId, 'equipo_id' => $equipo->id, 'tipo' => 'jugador', 'token' => \Illuminate\Support\Str::random(40), 'reutilizable' => true]);
    }

    protected function dt(Equipo $equipo): User
    {
        $dt = User::factory()->create(['equipo_id' => $equipo->id]);
        $dt->roles()->attach(Role::firstOrCreate(['nombre' => 'dt'])->id);

        return $dt;
    }

    public function test_no_puede_sumarse_por_link_a_otro_equipo_del_mismo_campeonato(): void
    {
        $inv = $this->invitacion($this->tigres);

        $this->actingAs($this->pedro)->from(route('invitacion.show', $inv->token))
            ->post(route('invitacion.aceptar', $inv->token))
            ->assertSessionHasErrors(['cupo' => \App\Models\Jugador::mensajeOtroEquipo($this->leones)]);

        $this->assertSame(1, Jugador::withoutGlobalScopes()->where('user_id', $this->pedro->id)->count());
    }

    public function test_si_puede_jugar_en_otro_campeonato(): void
    {
        $otra = Liga::create(['nombre' => 'Otra Liga', 'slug' => 'otra', 'owner_id' => $this->pedro->id, 'activa' => true, 'publica' => false]);
        $pumas = Equipo::forceCreate(['liga_id' => $otra->id, 'nombre' => 'Pumas', 'modalidad_id' => Modalidad::value('id')]);
        $inv = $this->invitacion($pumas, $otra->id);

        $this->actingAs($this->pedro)->post(route('invitacion.aceptar', $inv->token))->assertSessionHasNoErrors();
        $this->assertSame(2, Jugador::withoutGlobalScopes()->where('user_id', $this->pedro->id)->count());
    }

    public function test_la_carga_masiva_avisa_si_un_email_ya_juega_en_otro_equipo(): void
    {
        Mail::fake();
        $libro = new Spreadsheet;
        $libro->getActiveSheet()->fromArray([['Nombre', 'Email'], ['Pedro', 'Pedro@Correo.cl'], ['Nuevo', 'nuevo@correo.cl']]);
        $ruta = tempnam(sys_get_temp_dir(), 'carga').'.xlsx';
        (new Xlsx($libro))->save($ruta);

        // El archivo trae solo Nombre y Email (sin las columnas opcionales Dorsal y Posición)
        $this->actingAs($this->dt($this->tigres))
            ->post(route('carga.jugadores.importar', $this->tigres), ['archivo' => new UploadedFile($ruta, 'p.xlsx', null, null, true)])
            ->assertSessionHas('errores_filas', fn ($e) => array_keys($e) === [2] && str_contains($e[2][0], 'ya juega en Leones'));
        $this->assertSame(0, $this->tigres->jugadores()->count());
    }

    public function test_el_dt_no_puede_sumarse_a_su_equipo_si_ya_juega_en_otro_del_campeonato(): void
    {
        $dt = $this->dt($this->tigres);
        Jugador::withoutGlobalScopes()->forceCreate(['equipo_id' => $this->leones->id, 'user_id' => $dt->id, 'nombre' => 'DT jugador', 'activo' => true]);

        $this->actingAs($dt)->post(route('jugadores.unirme', $this->tigres))->assertSessionHasErrors('jugar');
        $this->assertSame(0, $this->tigres->jugadores()->count());
    }

    public function test_el_organizador_mueve_a_un_jugador_de_equipo_y_el_dt_no(): void
    {
        $ficha = Jugador::withoutGlobalScopes()->where('user_id', $this->pedro->id)->first();
        $admin = User::factory()->create();
        $admin->ligas()->attach(1, ['rol' => 'admin']);

        $this->actingAs($admin)->get(route('jugadores.edit', $ficha))->assertOk()->assertSee('name="equipo_id"', false);
        $this->actingAs($admin)->put(route('jugadores.update', $ficha), ['nombre' => 'Pedro', 'equipo_id' => $this->tigres->id])->assertSessionHasNoErrors();
        $this->assertSame($this->tigres->id, $ficha->fresh()->equipo_id);

        $dtTigres = $this->dt($this->tigres);
        $this->actingAs($dtTigres)->put(route('jugadores.update', $ficha), ['nombre' => 'Pedro', 'equipo_id' => $this->leones->id])->assertForbidden();
    }
}
