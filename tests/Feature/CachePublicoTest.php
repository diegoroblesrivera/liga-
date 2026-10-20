<?php

namespace Tests\Feature;

use App\Models\Equipo;
use App\Models\Gol;
use App\Models\Jugador;
use App\Models\Liga;
use App\Models\Modalidad;
use App\Models\Partido;
use App\Models\Torneo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

// Requiere MySQL (las migraciones usan vistas, triggers y ALTER ... ENUM).
class CachePublicoTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_pagina_publica_se_cachea_pero_un_gol_nuevo_se_ve_al_instante(): void
    {
        $liga = Liga::findOrFail(1);
        $liga->update(['publica' => true, 'activa' => true]);
        $modalidad = Modalidad::value('id');
        $a = Equipo::forceCreate(['liga_id' => 1, 'nombre' => 'Leones', 'modalidad_id' => $modalidad]);
        $b = Equipo::forceCreate(['liga_id' => 1, 'nombre' => 'Tigres', 'modalidad_id' => $modalidad]);
        $torneo = Torneo::forceCreate(['liga_id' => 1, 'nombre' => 'Apertura']);
        $p = Partido::withoutGlobalScopes()->forceCreate(['torneo_id' => $torneo->id, 'numero_fecha' => 1, 'equipo_local_id' => $a->id, 'equipo_visita_id' => $b->id, 'estado' => 'en_juego']);
        $pedro = Jugador::withoutGlobalScopes()->forceCreate(['equipo_id' => $a->id, 'nombre' => 'Pedro Goleador', 'activo' => true]);

        $url = route('campeonatos.publico', $liga);
        $this->get($url)->assertOk()->assertDontSee('Pedro Goleador');

        // Cacheada: un cambio por fuera de los modelos (sin eventos) no se ve
        DB::table('equipos')->where('id', $b->id)->update(['nombre' => 'Tigres Renombrado']);
        $this->get($url)->assertDontSee('Tigres Renombrado');

        // Un gol (evento del modelo) invalida la caché de ese campeonato
        Gol::withoutGlobalScopes()->forceCreate(['partido_id' => $p->id, 'jugador_id' => $pedro->id, 'equipo_id' => $a->id, 'tipo' => 'normal']);
        $this->get($url)->assertSee('Pedro Goleador')->assertSee('Tigres Renombrado');

        // La portada también
        $this->get(route('home'))->assertOk()->assertSee($liga->nombre);
    }
}
