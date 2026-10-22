<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Equipo;
use App\Models\Gol;
use App\Models\Jugador;
use App\Models\Liga;
use App\Models\ListaFecha;
use App\Models\Modalidad;
use App\Models\Partido;
use App\Models\Role;
use App\Models\Torneo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Requiere MySQL (las migraciones usan vistas, triggers y ALTER ... ENUM).
class ListaAsistenciaTest extends TestCase
{
    use RefreshDatabase;

    protected Liga $liga;
    protected Torneo $torneo;
    protected Partido $partido;
    protected User $admin;
    protected User $arbitro;
    protected User $dtLocal;
    protected User $jugadorUser;
    protected Jugador $jugador;
    protected Jugador $rival;

    protected function setUp(): void
    {
        parent::setUp();

        $this->liga = Liga::findOrFail(1);
        $this->liga->update(['pedir_lista' => true]);

        $modalidad = Modalidad::value('id');
        $local = Equipo::forceCreate(['liga_id' => 1, 'nombre' => 'Locales', 'modalidad_id' => $modalidad]);
        $visita = Equipo::forceCreate(['liga_id' => 1, 'nombre' => 'Visitas', 'modalidad_id' => $modalidad]);
        $this->torneo = Torneo::forceCreate(['liga_id' => 1, 'nombre' => 'Apertura']);

        $this->admin = User::factory()->create();
        $this->admin->ligas()->attach(1, ['rol' => 'admin']);
        $this->arbitro = User::factory()->create();
        $this->arbitro->ligas()->attach(1, ['rol' => 'arbitro']);

        $this->partido = Partido::withoutGlobalScopes()->forceCreate([
            'torneo_id' => $this->torneo->id, 'numero_fecha' => 3, 'equipo_local_id' => $local->id,
            'equipo_visita_id' => $visita->id, 'estado' => 'programado', 'arbitro_id' => $this->arbitro->id,
            'fecha_hora' => now()->setTime(20, 0),
        ]);

        $this->dtLocal = User::factory()->create(['equipo_id' => $local->id]);
        $this->dtLocal->roles()->attach(Role::firstOrCreate(['nombre' => 'dt'])->id);

        $this->jugadorUser = User::factory()->create();
        $this->jugador = Jugador::withoutGlobalScopes()->forceCreate(['equipo_id' => $local->id, 'user_id' => $this->jugadorUser->id, 'nombre' => 'Pedro Local', 'activo' => true]);
        $this->rival = Jugador::withoutGlobalScopes()->forceCreate(['equipo_id' => $visita->id, 'nombre' => 'Juan Visita', 'activo' => true]);
    }

    protected function urlQr(): string
    {
        return ListaFecha::deFecha($this->torneo, 3)->url();
    }

    protected function presentes(): int
    {
        return Asistencia::withoutGlobalScopes()->where('partido_id', $this->partido->id)->count();
    }

    public function test_el_jugador_escanea_el_qr_y_queda_presente_una_sola_vez(): void
    {
        $url = $this->urlQr();

        $this->get($url)->assertRedirect(route('login'));

        $this->actingAs($this->jugadorUser)->get($url)->assertOk()->assertSee('¡Quedaste presente!')->assertSee('Locales vs Visitas');
        $this->actingAs($this->jugadorUser)->get($url)->assertOk()->assertSee('Ya estabas en la lista');

        $this->assertSame(1, $this->presentes());
        $this->assertSame('qr', Asistencia::withoutGlobalScopes()->first()->metodo);
    }

    public function test_el_qr_no_marca_si_la_lista_esta_desactivada_otro_dia_o_si_no_juega(): void
    {
        $url = $this->urlQr();

        $this->actingAs(User::factory()->create())->get($url)->assertOk()->assertSee('No estás inscrito');

        $this->partido->forceFill(['fecha_hora' => now()->addDays(3)])->save();
        $this->actingAs($this->jugadorUser)->get($url)->assertOk()->assertSee('se abre el día del partido');

        $this->partido->forceFill(['fecha_hora' => now(), 'estado' => 'finalizado'])->save();
        $this->actingAs($this->jugadorUser)->get($url)->assertOk()->assertSee('ya está cerrada');

        $this->liga->update(['pedir_lista' => false]);
        $this->actingAs($this->jugadorUser)->get($url)->assertOk()->assertSee('no está pidiendo lista');

        $this->assertSame(0, $this->presentes());
        $this->actingAs($this->jugadorUser)->get(route('lista.escanear', 'token-que-no-existe'))->assertNotFound();
    }

    public function test_el_qr_de_la_fecha_lo_ven_organizador_arbitro_y_dt_pero_no_un_jugador(): void
    {
        $ruta = route('lista.fecha', [$this->torneo, 3]);

        foreach ([$this->admin, $this->arbitro, $this->dtLocal] as $u) {
            $this->actingAs($u)->get($ruta)->assertOk()->assertSee('<svg', false)->assertSee('Pedro Local');
        }
        $this->actingAs($this->admin)->get(route('lista.imprimir', [$this->torneo, 3]))->assertOk()->assertSee('<svg', false)->assertSee('Fecha 3');
        $this->actingAs($this->jugadorUser)->get($ruta)->assertForbidden();
        $this->actingAs($this->admin)->get(route('lista.fecha', [$this->torneo, 99]))->assertNotFound();
    }

    public function test_nuevo_qr_invalida_el_anterior_y_solo_lo_hace_el_organizador(): void
    {
        $viejo = $this->urlQr();

        $this->actingAs($this->dtLocal)->post(route('lista.regenerar', [$this->torneo, 3]))->assertForbidden();
        $this->actingAs($this->admin)->post(route('lista.regenerar', [$this->torneo, 3]))->assertRedirect();

        $this->actingAs($this->jugadorUser)->get($viejo)->assertNotFound();
        $this->actingAs($this->jugadorUser)->get($this->urlQr())->assertOk()->assertSee('¡Quedaste presente!');
    }

    public function test_marcar_a_mano_dt_solo_a_los_suyos_y_arbitro_a_todos(): void
    {
        $this->actingAs($this->dtLocal)->post(route('lista.marcar', $this->partido), ['jugador_id' => $this->rival->id])->assertForbidden();
        $this->actingAs($this->dtLocal)->post(route('lista.marcar', $this->partido), ['jugador_id' => $this->jugador->id])->assertRedirect();
        $this->actingAs($this->arbitro)->post(route('lista.marcar', $this->partido), ['jugador_id' => $this->rival->id])->assertRedirect();
        $this->actingAs($this->jugadorUser)->post(route('lista.marcar', $this->partido), ['jugador_id' => $this->jugador->id])->assertForbidden();

        $this->assertSame(2, $this->presentes());
        $this->assertSame('manual', Asistencia::withoutGlobalScopes()->first()->metodo);

        $this->actingAs($this->dtLocal)->delete(route('lista.desmarcar', [$this->partido, $this->jugador]))->assertRedirect();
        $this->assertSame(1, $this->presentes());
    }

    public function test_con_lista_obligatoria_sin_asistencia_no_puede_tener_goles(): void
    {
        $this->partido->forceFill(['estado' => 'en_juego'])->save();
        $gol = fn () => $this->actingAs($this->arbitro)->post(route('goles.store', $this->partido), ['jugador_id' => $this->jugador->id, 'tipo' => 'normal']);

        // Lista activada pero no obligatoria: se juega igual
        $gol()->assertSessionHasNoErrors();
        $this->assertSame(1, Gol::withoutGlobalScopes()->count());

        $this->liga->update(['lista_obligatoria' => true]);
        $gol()->assertSessionHasErrors('jugador_id');
        $this->assertSame(1, Gol::withoutGlobalScopes()->count());

        $this->actingAs($this->arbitro)->get(route('partidos.show', $this->partido))->assertOk()->assertSee('Pedro Local (sin lista)')
            ->assertSeeInOrder(['⚽', 'Registrar gol', 'Fecha y hora', 'Cancha', 'Registrar tarjeta']);

        $this->actingAs($this->arbitro)->post(route('lista.marcar', $this->partido), ['jugador_id' => $this->jugador->id]);
        $gol()->assertSessionHasNoErrors();
        $this->assertSame(2, Gol::withoutGlobalScopes()->count());
    }

    public function test_el_organizador_configura_la_lista_y_la_ficha_la_muestra(): void
    {
        $this->actingAs($this->dtLocal)->put(route('campeonatos.lista'), ['pedir_lista' => 1])->assertForbidden();

        $this->actingAs($this->admin)->put(route('campeonatos.lista'), ['lista_obligatoria' => 1])->assertRedirect();
        $this->assertFalse($this->liga->fresh()->pedir_lista);
        $this->assertFalse($this->liga->fresh()->lista_obligatoria);

        $this->actingAs($this->admin)->put(route('campeonatos.lista'), ['pedir_lista' => 1, 'lista_obligatoria' => 1])->assertRedirect();
        $this->assertTrue($this->liga->fresh()->exigeLista());

        $this->actingAs($this->admin)->get(route('partidos.show', $this->partido))->assertOk()->assertSee('Lista de asistencia')->assertSee('Mostrar QR de la fecha');
        $this->actingAs($this->jugadorUser)->get(route('partidos.show', $this->partido))->assertOk()->assertSee('Aún no estás en la lista');
        $this->actingAs($this->admin)->get(route('partidos.index'))->assertOk()->assertSee('Lista y QR de la fecha 3');
        $this->actingAs($this->admin)->get(route('campeonatos.edit'))->assertOk()->assertSee('Si no presenta la asistencia, no puede jugar');
    }
}
