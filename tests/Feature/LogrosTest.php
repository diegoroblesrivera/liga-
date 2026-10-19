<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Equipo;
use App\Models\Gol;
use App\Models\Jugador;
use App\Models\Liga;
use App\Models\Logro;
use App\Models\Modalidad;
use App\Models\Partido;
use App\Models\PichangaFecha;
use App\Models\Tarjeta;
use App\Models\Torneo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

// Requiere MySQL (las migraciones usan vistas, triggers y ALTER ... ENUM).
class LogrosTest extends TestCase
{
    use RefreshDatabase;

    protected Liga $liga;
    protected Torneo $torneo;
    protected Equipo $a;
    protected Equipo $b;
    protected Jugador $pedro;
    protected Jugador $arquero;
    protected Jugador $juan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->liga = Liga::findOrFail(1);
        $modalidad = Modalidad::value('id');
        $this->a = Equipo::forceCreate(['liga_id' => 1, 'nombre' => 'Leones', 'modalidad_id' => $modalidad]);
        $this->b = Equipo::forceCreate(['liga_id' => 1, 'nombre' => 'Tigres', 'modalidad_id' => $modalidad]);
        $this->torneo = Torneo::forceCreate(['liga_id' => 1, 'nombre' => 'Apertura']);

        $this->pedro = Jugador::withoutGlobalScopes()->forceCreate(['equipo_id' => $this->a->id, 'nombre' => 'Pedro', 'posicion' => 'Delantero', 'activo' => true]);
        $this->arquero = Jugador::withoutGlobalScopes()->forceCreate(['equipo_id' => $this->a->id, 'nombre' => 'Tito', 'posicion' => 'Arquero', 'activo' => true]);
        $this->juan = Jugador::withoutGlobalScopes()->forceCreate(['equipo_id' => $this->b->id, 'nombre' => 'Juan', 'posicion' => 'Defensa', 'activo' => true]);
    }

    // Partido jugado: goles por jugador (en orden), presentes en la lista y luego se finaliza.
    protected function jugar(int $fecha, array $goles = [], array $presentes = [], bool $finalizar = true): Partido
    {
        $p = Partido::withoutGlobalScopes()->forceCreate([
            'torneo_id' => $this->torneo->id, 'numero_fecha' => $fecha, 'equipo_local_id' => $this->a->id,
            'equipo_visita_id' => $this->b->id, 'estado' => 'en_juego', 'fecha_hora' => now()->subWeeks(30 - $fecha),
        ]);

        $gl = $gv = 0;
        foreach ($goles as [$jugador, $n]) {
            for ($i = 0; $i < $n; $i++) {
                Gol::withoutGlobalScopes()->forceCreate(['partido_id' => $p->id, 'jugador_id' => $jugador->id, 'equipo_id' => $jugador->equipo_id, 'tipo' => 'normal']);
            }
            $jugador->equipo_id === $this->a->id ? $gl += $n : $gv += $n;
        }
        foreach ($presentes as $jugador) {
            Asistencia::create(['partido_id' => $p->id, 'jugador_id' => $jugador->id, 'metodo' => 'manual']);
        }

        if ($finalizar) {
            $p->update(['estado' => 'finalizado', 'goles_local' => $gl, 'goles_visita' => $gv, 'finalizado_en' => $p->fecha_hora]);
        }

        return $p;
    }

    protected function claves(Jugador $j): array
    {
        return Logro::where('jugador_id', $j->id)->orderBy('clave')->pluck('clave')->all();
    }

    protected function hacerPro(): void
    {
        $this->liga->forceFill(['pro_hasta' => now()->addYear()])->save();
    }

    public function test_al_finalizar_un_partido_se_otorgan_debut_gol_hat_trick_y_valla_invicta(): void
    {
        $p = $this->jugar(1, [[$this->pedro, 3]], [$this->arquero, $this->juan], finalizar: false);
        $this->assertSame(0, Logro::count(), 'Con el partido en juego todavía no hay logros');

        $p->update(['estado' => 'finalizado', 'goles_local' => 3, 'goles_visita' => 0]);

        $this->assertSame(['debut', 'hat_trick', 'primer_gol'], $this->claves($this->pedro));
        $this->assertSame(['debut', 'valla_invicta'], $this->claves($this->arquero));
        $this->assertSame(['debut'], $this->claves($this->juan));

        $logro = Logro::where('huella', 'hat_trick|j'.$this->pedro->id)->firstOrFail();
        $this->assertSame($p->id, $logro->partido_id);
        $this->assertSame('Hat-trick', $logro->definicion()['nombre']);
    }

    public function test_corregir_un_partido_finalizado_quita_el_logro_que_ya_no_se_cumple(): void
    {
        $p = $this->jugar(1, [[$this->pedro, 3]]);
        $this->assertContains('hat_trick', $this->claves($this->pedro));

        Gol::withoutGlobalScopes()->where('partido_id', $p->id)->first()->delete();
        $this->assertNotContains('hat_trick', $this->claves($this->pedro));
        $this->assertContains('primer_gol', $this->claves($this->pedro));

        // Reabrir el partido lo saca de la historia
        $p->update(['estado' => 'en_juego']);
        $this->assertSame([], $this->claves($this->pedro));
    }

    public function test_los_umbrales_guardan_el_partido_donde_se_alcanzaron(): void
    {
        $this->jugar(1, [[$this->pedro, 4]]);
        $this->jugar(2, [[$this->pedro, 4]]);
        $this->assertNotContains('goleador_10', $this->claves($this->pedro));

        $tercero = $this->jugar(3, [[$this->pedro, 2]]);
        $logro = Logro::where('huella', 'goleador_10|j'.$this->pedro->id)->firstOrFail();
        $this->assertSame($tercero->id, $logro->partido_id);
        $this->assertTrue($logro->obtenido_en->isSameDay($tercero->fecha_hora));
    }

    public function test_fair_play_con_10_partidos_seguidos_sin_tarjetas(): void
    {
        for ($f = 1; $f <= 9; $f++) {
            $this->jugar($f, [], [$this->juan]);
        }
        $this->assertNotContains('fair_play', $this->claves($this->juan));

        $this->jugar(10, [], [$this->juan]);
        $this->assertContains('fair_play', $this->claves($this->juan));
        $this->assertContains('partidos_10', $this->claves($this->juan));

        // Una tarjeta en el último corta la racha: ya no son 10 seguidos
        $ultimo = Partido::withoutGlobalScopes()->where('numero_fecha', 10)->first();
        Tarjeta::withoutGlobalScopes()->forceCreate(['partido_id' => $ultimo->id, 'jugador_id' => $this->juan->id, 'equipo_id' => $this->b->id, 'tipo' => 'amarilla']);
        $this->assertNotContains('fair_play', $this->claves($this->juan));
    }

    public function test_los_logros_pro_solo_se_ganan_con_el_campeonato_pro_y_no_se_pierden_al_vencer(): void
    {
        for ($f = 1; $f <= 5; $f++) {
            $this->jugar($f, [[$this->pedro, 1]]);
        }
        $this->assertNotContains('racha_goleadora', $this->claves($this->pedro), 'Gratis: no hay logros Pro');

        $this->hacerPro();
        $this->assertContains('racha_goleadora', $this->claves($this->pedro), 'Al pasar a Pro se otorgan los ya ganados');

        $this->liga->forceFill(['pro_hasta' => now()->subDay()])->save();
        $this->jugar(6, [[$this->juan, 1]]);
        $this->assertContains('racha_goleadora', $this->claves($this->pedro), 'Al vencer el Pro se conserva');
    }

    public function test_al_cerrar_el_torneo_campeon_pichichi_figura_y_asistencia_perfecta(): void
    {
        $this->hacerPro();
        $this->liga->update(['pedir_lista' => true]);

        $this->jugar(1, [[$this->pedro, 2], [$this->juan, 1]], [$this->pedro, $this->arquero]);
        $this->jugar(2, [[$this->pedro, 1]], [$this->pedro, $this->arquero]);
        $ultimo = $this->jugar(3, [[$this->juan, 1]], [$this->pedro], finalizar: false);

        // Fecha 1 cerrada: Pedro figura (2 goles). El torneo aún no termina.
        $this->assertContains('figura_fecha', $this->claves($this->pedro));
        $this->assertNotContains('campeon', $this->claves($this->pedro));

        // Con un partido pendiente no se puede cerrar
        $admin = User::factory()->create();
        $admin->ligas()->attach(1, ['rol' => 'admin']);
        $this->actingAs($admin)->post(route('torneos.cerrar', $this->torneo))->assertSessionHasErrors('torneo');

        // Todas las fechas creadas jugadas no bastan: el organizador cierra el torneo
        $ultimo->update(['estado' => 'finalizado', 'goles_local' => 0, 'goles_visita' => 1]);
        $this->assertNotContains('campeon', $this->claves($this->pedro));

        $this->actingAs($admin)->post(route('torneos.cerrar', $this->torneo))->assertSessionHasNoErrors();

        // Leones: 6 pts vs Tigres 3 → campeones Pedro y Tito; Pedro pichichi (3 goles).
        $this->assertSame(['asistencia_perfecta', 'campeon', 'debut', 'figura_fecha', 'pichichi', 'primer_gol'], $this->claves($this->pedro));
        $this->assertContains('campeon', $this->claves($this->arquero));
        $this->assertNotContains('asistencia_perfecta', $this->claves($this->arquero), 'Faltó a la fecha 3');
        $this->assertNotContains('campeon', $this->claves($this->juan));
        $this->assertSame($this->torneo->id, Logro::where('clave', 'campeon')->value('torneo_id'));

        // Reabrir quita los de cierre; la figura de la fecha se mantiene
        $this->actingAs($admin)->delete(route('torneos.reabrir', $this->torneo))->assertRedirect();
        $this->assertSame(['debut', 'figura_fecha', 'primer_gol'], $this->claves($this->pedro));
        $this->actingAs($admin)->get(route('torneos.edit', $this->torneo))->assertOk()->assertSee('Cerrar torneo');
    }

    public function test_en_un_campeonato_gratis_el_torneo_no_da_logros_pro(): void
    {
        $this->jugar(1, [[$this->pedro, 2]]);

        $this->assertSame(['debut', 'primer_gol'], $this->claves($this->pedro));
        $this->assertSame(0, Logro::whereIn('clave', ['campeon', 'pichichi', 'figura_fecha'])->count());
    }

    public function test_pichangas_jugadas_dan_logros_al_usuario(): void
    {
        $user = User::factory()->create();
        $pichangaId = DB::table('pichangas')->insertGetId([
            'nombre' => 'Martes', 'dia_semana' => 2, 'hora' => '20:00', 'token' => Str::random(40), 'created_at' => now(), 'updated_at' => now(),
        ]);

        for ($i = 1; $i <= 10; $i++) {
            $fecha = PichangaFecha::forceCreate(['pichanga_id' => $pichangaId, 'fecha' => now()->subWeeks(11 - $i)->toDateString(), 'hora' => '20:00', 'estado' => 'abierta']);
            DB::table('pichanga_asistencias')->insert([
                'pichanga_fecha_id' => $fecha->id, 'user_id' => $user->id, 'estado' => 'confirmado',
                'anotado_en' => now(), 'goles' => $i === 4 ? 3 : 0, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $fecha->update(['estado' => 'jugada']);

            if ($i === 9) {
                $this->assertSame(['pichanga_hat_trick'], Logro::where('user_id', $user->id)->pluck('clave')->all());
            }
        }

        $this->assertSame(['pichanga_hat_trick', 'pichanguero_10'], Logro::where('user_id', $user->id)->orderBy('clave')->pluck('clave')->all());
    }

    public function test_el_comando_recalcula_la_historia(): void
    {
        $this->jugar(1, [[$this->pedro, 1]]);
        Logro::query()->delete();

        $this->artisan('logros:recalcular')->assertSuccessful();

        $this->assertSame(['debut', 'primer_gol'], $this->claves($this->pedro));
    }
}
