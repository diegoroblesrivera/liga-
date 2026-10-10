<?php

namespace Tests\Feature;

use App\Models\Cancha;
use App\Models\Equipo;
use App\Models\Invitacion;
use App\Models\Liga;
use App\Models\Modalidad;
use App\Models\Partido;
use App\Models\Torneo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

// Requiere MySQL (las migraciones usan vistas, triggers y ALTER ... ENUM).
class MultiLigaTest extends TestCase
{
    use RefreshDatabase;

    protected function crearCampeonato(User $user, string $nombre, bool $publica = true): Liga
    {
        $this->actingAs($user)->post(route('campeonatos.store'), [
            'nombre' => $nombre, 'descripcion' => "Desc {$nombre}", 'publica' => $publica ? '1' : '0',
        ])->assertRedirect(route('configuracion.index'));

        return Liga::where('nombre', $nombre)->latest('id')->firstOrFail();
    }

    protected function equipo(Liga $liga, string $nombre): Equipo
    {
        return Equipo::forceCreate(['liga_id' => $liga->id, 'nombre' => $nombre, 'modalidad_id' => Modalidad::value('id')]);
    }

    public function test_cualquier_usuario_crea_su_campeonato_y_queda_como_dueno(): void
    {
        $user = User::factory()->create();
        $this->assertFalse($user->participaEnTorneos());

        $liga = $this->crearCampeonato($user, 'Liga del Barrio');

        $this->assertSame('liga-del-barrio', $liga->slug);
        $this->assertSame($user->id, $liga->owner_id);
        $this->assertTrue($user->fresh()->esAdminDeLiga($liga->id));
        $this->assertFalse($user->fresh()->esAdminDeLiga(1)); // no es admin del campeonato 1

        $this->actingAs($user)->get(route('configuracion.index'))->assertOk()->assertSee('Liga del Barrio');
        $this->actingAs($user)->get(route('equipos.create'))->assertOk();

        // Slug único aunque se repita el nombre
        $otra = $this->crearCampeonato(User::factory()->create(), 'Liga del Barrio');
        $this->assertSame('liga-del-barrio-2', $otra->slug);
    }

    public function test_un_campeonato_no_ve_ni_toca_los_datos_de_otro(): void
    {
        $duenoA = User::factory()->create();
        $duenoB = User::factory()->create();
        $ligaA = $this->crearCampeonato($duenoA, 'Liga A');
        $ligaB = $this->crearCampeonato($duenoB, 'Liga B');

        $tigres = $this->equipo($ligaA, 'Tigres');
        $leones = $this->equipo($ligaB, 'Leones');
        $torneoA = Torneo::forceCreate(['liga_id' => $ligaA->id, 'nombre' => 'Apertura A']);
        $canchaA = Cancha::forceCreate(['liga_id' => $ligaA->id, 'nombre' => 'Cancha A']);
        $partidoA = Partido::forceCreate([
            'torneo_id' => $torneoA->id, 'numero_fecha' => 1, 'equipo_local_id' => $tigres->id,
            'equipo_visita_id' => $this->equipo($ligaA, 'Pumas')->id, 'estado' => 'programado',
        ]);

        // Listados: cada uno ve solo lo suyo
        $this->actingAs($duenoB)->get(route('equipos.index'))->assertOk()->assertSee('Leones')->assertDontSee('Tigres');
        $this->actingAs($duenoB)->get(route('torneos.index'))->assertOk()->assertDontSee('Apertura A');
        $this->actingAs($duenoB)->get(route('canchas.index'))->assertOk()->assertDontSee('Cancha A');
        $this->actingAs($duenoA)->get(route('equipos.index'))->assertOk()->assertSee('Tigres')->assertDontSee('Leones');

        // Acceso directo por URL a datos ajenos: no existen para él
        $this->actingAs($duenoB)->get(route('equipos.show', $tigres))->assertNotFound();
        $this->actingAs($duenoB)->get(route('partidos.show', $partidoA))->assertNotFound();
        $this->actingAs($duenoB)->put(route('equipos.update', $tigres), ['nombre' => 'Hackeado'])->assertNotFound();
        $this->actingAs($duenoB)->delete(route('canchas.destroy', $canchaA))->assertNotFound();
        $this->assertSame('Tigres', $tigres->fresh()->nombre);
        $this->assertNotNull($canchaA->fresh());

        // IDs ajenos enviados a mano en un formulario: rechazados
        $torneoB = Torneo::forceCreate(['liga_id' => $ligaB->id, 'nombre' => 'Apertura B']);
        $this->actingAs($duenoB)->post(route('partidos.store'), [
            'torneo_id' => $torneoB->id, 'numero_fecha' => 1,
            'equipo_local_id' => $leones->id, 'equipo_visita_id' => $tigres->id,
            'cancha_id' => $canchaA->id,
        ])->assertSessionHasErrors(['equipo_visita_id', 'cancha_id']);
        $this->assertSame(1, Partido::withoutGlobalScopes()->count());
    }

    public function test_lo_que_crea_el_admin_queda_en_su_campeonato(): void
    {
        $dueno = User::factory()->create();
        $liga = $this->crearCampeonato($dueno, 'Liga Nueva');

        $this->actingAs($dueno)->post(route('canchas.store'), ['nombre' => 'Complejo Sur'])->assertRedirect();
        $this->actingAs($dueno)->post(route('torneos.store'), ['nombre' => 'Clausura'])->assertRedirect();
        $this->actingAs($dueno)->post(route('equipos.store'), ['nombre' => 'Los Cracks', 'modalidad_id' => Modalidad::value('id')])->assertRedirect();

        $this->assertSame($liga->id, Cancha::withoutGlobalScopes()->where('nombre', 'Complejo Sur')->value('liga_id'));
        $this->assertSame($liga->id, Torneo::withoutGlobalScopes()->where('nombre', 'Clausura')->value('liga_id'));
        $this->assertSame($liga->id, Equipo::withoutGlobalScopes()->where('nombre', 'Los Cracks')->value('liga_id'));

        // Otro campeonato puede tener un equipo con el mismo nombre
        $otro = User::factory()->create();
        $this->crearCampeonato($otro, 'Otra Liga');
        $this->actingAs($otro)->post(route('equipos.store'), ['nombre' => 'Los Cracks', 'modalidad_id' => Modalidad::value('id')])
            ->assertSessionHasNoErrors();
        // ...pero no repetido dentro del mismo
        $this->actingAs($otro)->post(route('equipos.store'), ['nombre' => 'Los Cracks', 'modalidad_id' => Modalidad::value('id')])
            ->assertSessionHasErrors('nombre');
    }

    public function test_cambiar_de_campeonato_y_no_poder_usar_uno_ajeno(): void
    {
        $user = User::factory()->create();
        $ligaA = $this->crearCampeonato($user, 'Liga Uno');
        $ligaB = $this->crearCampeonato($user, 'Liga Dos'); // queda abierta la última creada
        $this->equipo($ligaA, 'Equipo de Uno');
        $this->equipo($ligaB, 'Equipo de Dos');

        $this->actingAs($user)->get(route('equipos.index'))->assertSee('Equipo de Dos')->assertDontSee('Equipo de Uno');

        $this->actingAs($user)->post(route('campeonatos.usar', $ligaA))->assertRedirect(route('dashboard'));
        $this->actingAs($user)->get(route('equipos.index'))->assertSee('Equipo de Uno')->assertDontSee('Equipo de Dos');
        $this->assertSame($ligaA->id, $user->fresh()->ultima_liga_id);

        $ajena = $this->crearCampeonato(User::factory()->create(), 'Liga Ajena');
        $this->actingAs($user)->post(route('campeonatos.usar', $ajena))->assertForbidden();
    }

    public function test_portada_y_campeonatos_publicos(): void
    {
        $publica = $this->crearCampeonato(User::factory()->create(), 'Liga Abierta', true);
        $privada = $this->crearCampeonato(User::factory()->create(), 'Liga Cerrada', false);
        $this->equipo($publica, 'Equipo Visible');

        auth()->logout();

        $this->get('/')
            ->assertOk()
            ->assertSee('Crea una pichanga')
            ->assertSee('Crea un campeonato')
            ->assertSee('Liga Abierta')
            ->assertDontSee('Liga Cerrada');

        $this->get(route('campeonatos.publico', $publica))->assertOk()->assertSee('Liga Abierta');
        $this->get(route('campeonatos.publico', $privada))->assertNotFound();

        // Crear campeonato sin sesión pide login
        $this->get(route('campeonatos.create'))->assertRedirect(route('login'));
    }

    public function test_el_dueno_cambia_el_campeonato_a_privado(): void
    {
        $dueno = User::factory()->create();
        $liga = $this->crearCampeonato($dueno, 'Liga Visible', true);

        $this->actingAs($dueno)->put(route('campeonatos.update'), ['nombre' => 'Liga Visible', 'publica' => '0'])
            ->assertRedirect(route('campeonatos.edit'));
        $this->assertFalse($liga->fresh()->publica);

        // Un jugador cualquiera no puede cambiar la configuración
        $jugador = User::factory()->create();
        $equipo = $this->equipo($liga, 'Equipo X');
        \App\Models\Jugador::forceCreate(['equipo_id' => $equipo->id, 'user_id' => $jugador->id, 'nombre' => $jugador->name]);
        $this->actingAs($jugador)->get(route('campeonatos.edit'))->assertForbidden();
    }

    public function test_la_invitacion_inscribe_en_el_campeonato_que_la_genero(): void
    {
        $liga = $this->crearCampeonato(User::factory()->create(), 'Liga Invita');
        $equipo = $this->equipo($liga, 'Los Invitados');
        $invitacion = Invitacion::forceCreate([
            'liga_id' => $liga->id, 'equipo_id' => $equipo->id, 'tipo' => 'jugador',
            'token' => Str::random(40), 'reutilizable' => true, 'usos' => 0,
        ]);
        $arbitraje = Invitacion::forceCreate([
            'liga_id' => $liga->id, 'equipo_id' => null, 'tipo' => 'arbitro',
            'token' => Str::random(40), 'reutilizable' => false, 'usos' => 0,
        ]);

        auth()->logout();

        $this->get(route('invitacion.show', $invitacion->token))->assertOk()->assertSee('Los Invitados');
        $this->post(route('invitacion.store', $invitacion->token), [
            'name' => 'Nuevo Jugador', 'email' => 'nuevo@test.cl',
            'password' => 'clave-segura-123', 'password_confirmation' => 'clave-segura-123',
        ])->assertRedirect(route('dashboard'));

        $nuevo = User::where('email', 'nuevo@test.cl')->firstOrFail();
        $this->assertSame([$liga->id], $nuevo->ligasIds());

        auth()->logout();
        $this->post(route('invitacion.store', $arbitraje->token), [
            'name' => 'Nueva Árbitro', 'email' => 'arbitro@test.cl',
            'password' => 'clave-segura-123', 'password_confirmation' => 'clave-segura-123',
        ])->assertRedirect(route('dashboard'));

        $arbitro = User::where('email', 'arbitro@test.cl')->firstOrFail();
        $this->assertTrue($arbitro->rolEnLiga(['arbitro'], $liga->id));
        $this->assertFalse($arbitro->rolEnLiga(['arbitro'], 1));
    }

    public function test_el_super_admin_entra_a_todos_los_campeonatos(): void
    {
        $this->crearCampeonato(User::factory()->create(), 'Liga Cualquiera');
        $super = User::factory()->create(['is_super_admin' => true]);

        $this->assertCount(Liga::count(), $super->ligasIds());
        $this->assertTrue($super->esAdminDeLiga(1));
    }
}
