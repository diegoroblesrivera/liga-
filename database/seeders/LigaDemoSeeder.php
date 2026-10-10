<?php

namespace Database\Seeders;

use App\Models\Cancha;
use App\Models\Equipo;
use App\Models\Gol;
use App\Models\Jugador;
use App\Models\Liga;
use App\Models\Modalidad;
use App\Models\Partido;
use App\Models\Role;
use App\Models\Torneo;
use App\Models\User;
use App\Support\LigaActual;
use Illuminate\Database\Seeder;

class LigaDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Roles y modalidades ya vienen sembrados por las migraciones.
        $roles = Role::pluck('id', 'nombre'); // ['admin' => 1, 'dt' => 2, 'jugador' => 3, 'arbitro' => 4]
        $futbolito = Modalidad::where('nombre', 'Futbolito')->firstOrFail(); // cupo 10

        // ---------- Usuarios base ----------
        $admin = User::create([
            'name' => 'Administrador',
            'email' => 'admin@liga.test',
            'password' => 'password', // se hashea solo por el cast 'hashed' del modelo
        ]);
        $admin->roles()->attach($roles['admin']);

        $arbitro = User::create([
            'name' => 'Ana Árbitro',
            'email' => 'arbitro@liga.test',
            'password' => 'password',
        ]);
        $arbitro->roles()->attach($roles['arbitro']);

        // ---------- Campeonato ----------
        // La liga 1 la crea la migración de ligas; todo lo que sigue pertenece a ella.
        $liga = Liga::findOrFail(1);
        $liga->update(['owner_id' => $admin->id]);
        $liga->usuarios()->syncWithoutDetaching([
            $admin->id => ['rol' => 'owner'],
            $arbitro->id => ['rol' => 'arbitro'],
        ]);
        LigaActual::fijar($liga);

        // ---------- Equipos (los crea el admin) ----------
        $tigres = Equipo::create(['nombre' => 'Los Tigres', 'modalidad_id' => $futbolito->id]);
        $condor = Equipo::create(['nombre' => 'Real Cóndor', 'modalidad_id' => $futbolito->id]);

        // ---------- DTs (cada uno dirige su equipo) ----------
        $dt1 = User::create([
            'name' => 'Carlos DT',
            'email' => 'dt1@liga.test',
            'password' => 'password',
            'equipo_id' => $tigres->id,
        ]);
        // Carlos es DT y además jugador (demo de rol múltiple)
        $dt1->roles()->attach([$roles['dt'], $roles['jugador']]);

        $dt2 = User::create([
            'name' => 'Pedro DT',
            'email' => 'dt2@liga.test',
            'password' => 'password',
            'equipo_id' => $condor->id,
        ]);
        $dt2->roles()->attach($roles['dt']);

        // ---------- Jugadores ----------
        // Carlos (DT1) también tiene ficha de jugador enlazada a su cuenta
        $carlos = Jugador::create([
            'equipo_id' => $tigres->id,
            'user_id' => $dt1->id,
            'nombre' => 'Carlos DT',
            'dorsal' => 10,
            'posicion' => 'Mediocampista',
        ]);

        $tigresJug = collect(['Luis Pérez', 'Marco Díaz', 'Iván Soto', 'Diego Rojas'])
            ->map(fn ($nombre, $i) => Jugador::create([
                'equipo_id' => $tigres->id,
                'nombre' => $nombre,
                'dorsal' => $i + 1,
            ]));

        $condorJug = collect(['Juan Vega', 'Nico Fuentes', 'Bruno Lara', 'Seba Muñoz', 'Tomás Reyes'])
            ->map(fn ($nombre, $i) => Jugador::create([
                'equipo_id' => $condor->id,
                'nombre' => $nombre,
                'dorsal' => $i + 1,
            ]));

        // ---------- Torneo y canchas ----------
        $torneo = Torneo::create(['nombre' => 'Torneo Apertura 2026', 'temporada' => '2026']);
        $torneo->equipos()->attach([$tigres->id, $condor->id]);

        $municipal = Cancha::create(['nombre' => 'Cancha Municipal', 'direccion' => 'Av. Principal 123']);
        $sur = Cancha::create(['nombre' => 'Complejo Sur']);

        // ---------- Partido 1: finalizado, Tigres 2 - 1 Cóndor ----------
        $partido1 = Partido::create([
            'torneo_id' => $torneo->id,
            'numero_fecha' => 1,
            'equipo_local_id' => $tigres->id,
            'equipo_visita_id' => $condor->id,
            'cancha_id' => $municipal->id,
            'arbitro_id' => $arbitro->id,
            'fecha_hora' => now()->subDays(7),
            'goles_local' => 2,
            'goles_visita' => 1,
            'estado' => 'finalizado',
            'iniciado_en' => now()->subDays(7),
            'finalizado_en' => now()->subDays(7)->addHour(),
        ]);

        // Goles del partido 1 (pueblan vista_posiciones y vista_goleadores)
        Gol::create(['partido_id' => $partido1->id, 'jugador_id' => $carlos->id,        'equipo_id' => $tigres->id, 'minuto' => 23, 'tipo' => 'normal']);
        Gol::create(['partido_id' => $partido1->id, 'jugador_id' => $tigresJug[0]->id,  'equipo_id' => $tigres->id, 'minuto' => 58, 'tipo' => 'penal']);
        Gol::create(['partido_id' => $partido1->id, 'jugador_id' => $condorJug[0]->id,  'equipo_id' => $condor->id, 'minuto' => 71, 'tipo' => 'normal']);

        // ---------- Partido 2: programado (fixture futuro) ----------
        Partido::create([
            'torneo_id' => $torneo->id,
            'numero_fecha' => 2,
            'equipo_local_id' => $condor->id,
            'equipo_visita_id' => $tigres->id,
            'cancha_id' => $sur->id,
            'arbitro_id' => $arbitro->id,
            'fecha_hora' => now()->addDays(3),
            'estado' => 'programado',
        ]);
    }
}
