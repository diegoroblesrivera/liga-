<?php

namespace Tests\Feature;

use App\Models\Equipo;
use App\Models\Etapa;
use App\Models\Modalidad;
use App\Models\Partido;
use App\Models\Torneo;
use App\Models\User;
use App\Support\Torneos\Generador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Requiere MySQL (las migraciones usan vistas, triggers y ALTER ... ENUM).
class FormatosTorneoTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->admin->ligas()->attach(1, ['rol' => 'admin']);
    }

    protected function equipos(int $n): array
    {
        $modalidad = Modalidad::value('id');

        return collect(range(1, $n))->map(fn ($i) => Equipo::forceCreate(['liga_id' => 1, 'nombre' => sprintf('Equipo %02d', $i), 'modalidad_id' => $modalidad])->id)->all();
    }

    protected function crear(array $datos)
    {
        return $this->actingAs($this->admin)->post(route('fixtures.store'), $datos + [
            'nombre' => 'Apertura', 'fecha_inicio' => '2026-11-02', 'dias_semana' => [3], 'partidos_por_dia' => 8, 'hora_defecto' => '21:00',
        ]);
    }

    protected function partidos(): \Illuminate\Support\Collection
    {
        return Partido::withoutGlobalScopes()->orderBy('numero_fecha')->orderBy('llave')->get();
    }

    // Finaliza un partido: gana el equipo con menor id salvo que se indique otro marcador.
    protected function jugar(Partido $p, ?array $marcador = null, ?array $penales = null): void
    {
        [$gl, $gv] = $marcador ?? ($p->equipo_local_id < $p->equipo_visita_id ? [2, 0] : [0, 2]);
        $p->refresh()->update(['estado' => 'finalizado', 'goles_local' => $gl, 'goles_visita' => $gv,
            'penales_local' => $penales[0] ?? null, 'penales_visita' => $penales[1] ?? null]);
    }

    public function test_generador_round_robin_y_siembra(): void
    {
        $this->assertSame([1, 8, 4, 5, 2, 7, 3, 6], Generador::siembra(8));
        $this->assertSame([1, 4, 2, 3], Generador::siembra(4));

        $ida = Generador::roundRobin([1, 2, 3, 4, 5], false);
        $pares = collect($ida)->flatten(1)->map(fn ($p) => min($p).'-'.max($p));
        $this->assertCount(5, $ida, '5 equipos: 5 fechas (uno libre por fecha)');
        $this->assertCount(10, $pares);
        $this->assertSame(10, $pares->unique()->count(), 'Cada cruce una sola vez');

        $this->assertCount(10, Generador::roundRobin([1, 2, 3, 4, 5], true));
    }

    public function test_liga_de_una_rueda_y_de_ida_y_vuelta(): void
    {
        $this->equipos(6);

        $this->crear(['etapas' => 1, 'tipo' => 'liga'])->assertRedirect(route('partidos.index'));
        $this->assertSame(15, Partido::withoutGlobalScopes()->count());
        $this->assertSame(5, $this->partidos()->max('numero_fecha'));
        $this->assertTrue($this->partidos()->every(fn ($p) => $p->fecha_hora->isWednesday() && $p->fecha_hora->format('H:i') === '21:00'));

        $this->crear(['etapas' => 1, 'tipo' => 'liga', 'ida_vuelta' => 1, 'nombre' => 'Clausura']);
        $this->assertSame(30, Partido::withoutGlobalScopes()->where('torneo_id', Torneo::where('nombre', 'Clausura')->value('id'))->count());
    }

    public function test_validaciones_de_formato(): void
    {
        $this->equipos(8);

        $this->crear(['etapas' => 1, 'tipo' => 'grupos', 'grupos' => 2, 'clasifican_grupo' => 2])->assertSessionHasErrors('etapas');
        $this->crear(['etapas' => 2, 'tipo' => 'grupos', 'grupos' => 3, 'clasifican_grupo' => 2])->assertSessionHasErrors('clasifican_grupo');
        $this->crear(['etapas' => 2, 'tipo' => 'grupos', 'grupos' => 4, 'clasifican_grupo' => 2])->assertSessionHasErrors('clasifican_grupo'); // grupos de 2: no pueden clasificar 2
        $this->crear(['etapas' => 1, 'tipo' => 'eliminacion', 'llave' => 8, 'equipos' => array_slice(Equipo::pluck('id')->all(), 0, 6)])->assertSessionHasErrors('equipos');
        $this->crear(['etapas' => 2, 'tipo' => 'liga', 'clasifican' => 16])->assertSessionHasErrors('clasifican');
        $this->assertSame(0, Torneo::count());
    }

    public function test_grupos_y_play_offs_con_penales_tercer_lugar_y_campeon(): void
    {
        $this->equipos(8);
        $this->crear(['etapas' => 2, 'tipo' => 'grupos', 'grupos' => 2, 'clasifican_grupo' => 2, 'tercer_lugar' => 1])->assertSessionHasNoErrors();

        $torneo = Torneo::firstOrFail();
        $grupos = Etapa::withoutGlobalScopes()->where('orden', 1)->firstOrFail();
        $this->assertSame('grupos', $grupos->tipo);
        $this->assertSame(['A' => 4, 'B' => 4], $grupos->equipos()->get()->groupBy('pivot.grupo')->map->count()->sortKeys()->all());
        $this->assertSame(12, $this->partidos()->count(), '2 grupos de 4: 6 partidos cada uno');
        $this->assertSame(3, $this->partidos()->max('numero_fecha'));
        $this->assertTrue($this->partidos()->every(fn ($p) => in_array($p->grupo, ['A', 'B'], true) && $p->ronda === null));

        // Admin ve la tabla por grupo; todavía no puede generar la eliminatoria
        $this->actingAs($this->admin)->get(route('tablas.posiciones'))->assertOk()->assertSee('Grupo A')->assertSee('Grupo B')->assertDontSee('Generar eliminatoria');
        $this->actingAs($this->admin)->post(route('fixtures.segunda', $torneo))->assertSessionHasErrors('torneo');

        // Se juega la fase de grupos: en cada grupo gana el de menor id
        $this->partidos()->each(fn ($p) => $this->jugar($p));
        $this->actingAs($this->admin)->get(route('tablas.posiciones'))->assertSee('Generar eliminatoria');
        $this->actingAs($this->admin)->post(route('fixtures.segunda', $torneo))->assertRedirect();

        $tablaA = $grupos->equipos()->wherePivot('grupo', 'A')->orderBy('equipos.id')->pluck('equipos.id')->all();
        $tablaB = $grupos->equipos()->wherePivot('grupo', 'B')->orderBy('equipos.id')->pluck('equipos.id')->all();
        $semis = $this->partidos()->where('ronda', 'semifinal')->values();
        $this->assertCount(2, $semis);
        $this->assertSame([$tablaA[0], $tablaB[1]], [$semis[0]->equipo_local_id, $semis[0]->equipo_visita_id], '1°A vs 2°B');
        $this->assertSame([$tablaB[0], $tablaA[1]], [$semis[1]->equipo_local_id, $semis[1]->equipo_visita_id], '1°B vs 2°A');
        $this->assertSame(4, $semis[0]->numero_fecha, 'Sigue la numeración de fechas');
        $this->assertTrue($semis[0]->fecha_hora->gt($this->partidos()->whereNull('ronda')->max('fecha_hora')));

        // Semifinal 1 empatada: el árbitro no puede cerrarla sin penales
        $arbitro = User::factory()->create();
        $arbitro->ligas()->attach(1, ['rol' => 'arbitro']);
        $semis[0]->update(['estado' => 'en_juego', 'arbitro_id' => $arbitro->id]);
        $this->actingAs($arbitro)->post(route('partidos.finalizar', $semis[0]))->assertSessionHasErrors('penales_local');
        $this->actingAs($arbitro)->post(route('partidos.finalizar', $semis[0]), ['penales_local' => 3, 'penales_visita' => 4])->assertSessionHasNoErrors();
        $this->assertSame($tablaB[1], $semis[0]->fresh()->ganadorId(), 'Gana a penales el 2°B');
        $this->assertSame(0, $this->partidos()->where('ronda', 'final')->count(), 'Falta la otra semifinal');

        $this->jugar($semis[1], [1, 0]);
        $final = $this->partidos()->firstWhere('ronda', 'final');
        $tercero = $this->partidos()->firstWhere('ronda', 'tercer_lugar');
        $this->assertSame([$tablaB[1], $tablaB[0]], [$final->equipo_local_id, $final->equipo_visita_id]);
        $this->assertSame([$tablaA[0], $tablaA[1]], [$tercero->equipo_local_id, $tercero->equipo_visita_id]);
        $this->assertSame($final->numero_fecha, $tercero->numero_fecha, 'Final y tercer lugar en la misma fecha');

        $this->jugar($tercero, [2, 1]);
        $this->jugar($final, [0, 1]);
        $this->assertSame(2, $this->partidos()->whereNotNull('ronda')->where('ronda', '!=', 'semifinal')->count(), 'No crea más rondas después de la final');

        $this->actingAs($this->admin)->get(route('tablas.posiciones'))->assertOk()
            ->assertSee('Semifinal')->assertSee('Final')->assertSee('Tercer lugar')->assertSee('(4)', false);
        $this->actingAs($this->admin)->get(route('partidos.index'))->assertOk()->assertSee('data-titulo="Final"', false);

        // El campeón (logro Pro) es el ganador de la final, no el primero de la tabla
        \App\Models\Liga::find(1)->forceFill(['pro_hasta' => now()->addYear()])->save();
        $jugador = \App\Models\Jugador::withoutGlobalScopes()->forceCreate(['equipo_id' => $tablaB[0], 'nombre' => 'Crack', 'activo' => true]);
        $this->actingAs($this->admin)->post(route('torneos.cerrar', $torneo))->assertSessionHasNoErrors();
        $this->assertTrue(\App\Models\Logro::where('clave', 'campeon')->where('jugador_id', $jugador->id)->exists());
    }

    public function test_liga_y_luego_eliminacion_de_los_primeros_por_ubicacion(): void
    {
        $ids = $this->equipos(5);
        $this->crear(['etapas' => 2, 'tipo' => 'liga', 'clasifican' => 4])->assertSessionHasNoErrors();
        $this->partidos()->each(fn ($p) => $this->jugar($p)); // gana siempre el de menor id: tabla 1°..5° por id

        $this->actingAs($this->admin)->post(route('fixtures.segunda', Torneo::firstOrFail()))->assertRedirect();
        $semis = $this->partidos()->where('ronda', 'semifinal')->values();
        $this->assertSame([[$ids[0], $ids[3]], [$ids[1], $ids[2]]], $semis->map(fn ($p) => [$p->equipo_local_id, $p->equipo_visita_id])->all(), '1° vs 4°, 2° vs 3°');
    }

    public function test_eliminacion_directa_de_8_equipos_elegidos(): void
    {
        $ids = $this->equipos(10);
        $elegidos = array_slice($ids, 2, 8);
        $this->crear(['etapas' => 1, 'tipo' => 'eliminacion', 'llave' => 8, 'equipos' => $elegidos])->assertSessionHasNoErrors();

        $cuartos = $this->partidos();
        $this->assertCount(4, $cuartos);
        $this->assertTrue($cuartos->every(fn ($p) => $p->ronda === 'cuartos'));
        $this->assertEqualsCanonicalizing($elegidos, $cuartos->flatMap(fn ($p) => [$p->equipo_local_id, $p->equipo_visita_id])->all());

        $cuartos->each(fn ($p) => $this->jugar($p));
        $this->assertSame(2, $this->partidos()->where('ronda', 'semifinal')->count());
        $this->actingAs($this->admin)->get(route('tablas.posiciones'))->assertOk()->assertSee('Cuartos de final')->assertSee('Por definir');
    }

    public function test_los_torneos_anteriores_sin_etapas_siguen_mostrando_su_tabla(): void
    {
        [$a, $b] = $this->equipos(2);
        $torneo = Torneo::forceCreate(['liga_id' => 1, 'nombre' => 'Antiguo']);
        Partido::withoutGlobalScopes()->forceCreate(['torneo_id' => $torneo->id, 'numero_fecha' => 1, 'equipo_local_id' => $a, 'equipo_visita_id' => $b, 'estado' => 'finalizado', 'goles_local' => 3, 'goles_visita' => 1]);

        $this->actingAs($this->admin)->get(route('tablas.posiciones'))->assertOk()->assertSee('Equipo 01')->assertDontSee('Grupo A');
    }
}
