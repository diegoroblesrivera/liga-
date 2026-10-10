<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EquipoController;
use App\Http\Controllers\InvitacionController;
use App\Http\Controllers\PartidoController;
use App\Http\Controllers\GolController;
use App\Http\Controllers\ArbitroController;
use App\Http\Controllers\FixtureController;
use App\Http\Controllers\TablaController;
use App\Http\Controllers\InscripcionController;
use App\Http\Controllers\JugadorController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PagoPartidoController;
use App\Http\Controllers\AlineacionController;
use App\Http\Controllers\ConvocatoriaController;
use App\Http\Controllers\TarjetaController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\CanchaController;
use App\Http\Controllers\TorneoController;
use App\Http\Controllers\PichangaController;
use App\Http\Controllers\PichangaFechaController;
use App\Http\Controllers\PichangaPagoController;
use App\Http\Controllers\CampeonatoController;
use App\Http\Controllers\CampeonatoPublicoController;


// Registro por invitación a un equipo (público)
Route::get('/invitacion/{token}', [InscripcionController::class, 'show'])->name('invitacion.show');
Route::post('/invitacion/{token}', [InscripcionController::class, 'store'])->name('invitacion.store');


Route::get('/',[HomeController::class, 'index'])->name('home');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::put('/profile/acceso', [ProfileController::class, 'updateAcceso'])->name('profile.acceso.update');

    // Campeonatos: cualquier usuario crea el suyo; cambiar entre los que participa
    Route::get('/campeonatos/crear', [CampeonatoController::class, 'create'])->name('campeonatos.create');
    Route::post('/campeonatos', [CampeonatoController::class, 'store'])->name('campeonatos.store');
    Route::post('/campeonatos/{liga}/usar', [CampeonatoController::class, 'usar'])->name('campeonatos.usar');
});

// Campeonato: solo quienes participan en algún torneo (admin, DT, jugador o árbitro).
Route::middleware(['auth', 'torneos'])->group(function () {
    // Equipos
    Route::get('/equipos', [EquipoController::class, 'index'])->name('equipos.index');
    Route::get('/equipos/crear', [EquipoController::class, 'create'])->name('equipos.create');
    Route::post('/equipos', [EquipoController::class, 'store'])->name('equipos.store');
    Route::get('/equipos/{equipo}', [EquipoController::class, 'show'])->name('equipos.show');
    Route::get('/equipos/{equipo}/editar', [EquipoController::class, 'edit'])->name('equipos.edit');
    Route::put('/equipos/{equipo}', [EquipoController::class, 'update'])->name('equipos.update');

    // Invitaciones (DT / jugador)
    Route::post('/equipos/{equipo}/invitaciones', [InvitacionController::class, 'store'])->name('invitaciones.store');
    Route::delete('/invitaciones/{invitacion}', [InvitacionController::class, 'destroy'])->name('invitaciones.destroy');

    // Partidos (fixture del admin)
    Route::get('/partidos', [PartidoController::class, 'index'])->name('partidos.index');
    Route::get('/partidos/crear', [PartidoController::class, 'create'])->name('partidos.create');
    Route::post('/partidos', [PartidoController::class, 'store'])->name('partidos.store');
    Route::get('/partidos/{partido}', [PartidoController::class, 'show'])->name('partidos.show');
    Route::get('/partidos/{partido}/editar', [PartidoController::class, 'edit'])->name('partidos.edit');
    Route::put('/partidos/{partido}', [PartidoController::class, 'update'])->name('partidos.update');
    Route::post('/partidos/{partido}/programacion', [PartidoController::class, 'actualizarProgramacion'])->name('partidos.actualizarProgramacion');
    Route::get('/equipos/{equipo}/alineacion', [App\Http\Controllers\AlineacionController::class, 'equipo'])->name('equipos.alineacion');
    // Partidos: acciones del árbitro y asignación
    Route::post('/partidos/{partido}/iniciar', [PartidoController::class, 'iniciar'])->name('partidos.iniciar');
    Route::post('/partidos/{partido}/finalizar', [PartidoController::class, 'finalizar'])->name('partidos.finalizar');
    Route::post('/partidos/{partido}/arbitro', [PartidoController::class, 'asignarArbitro'])->name('partidos.asignarArbitro');

    Route::get('/partidos/{partido}/pago/asignar', [PagoPartidoController::class, 'edit'])->name('partidos.pago.edit');
    Route::put('/partidos/{partido}/pago/asignar', [PagoPartidoController::class, 'update'])->name('partidos.pago.update');
    Route::get('/pagos/panel', [PagoPartidoController::class, 'panel'])->name('pagos.panel');

    Route::get('/partidos/{partido}/convocatoria/{equipo}', [ConvocatoriaController::class, 'edit'])->name('convocatorias.edit');
    Route::put('/partidos/{partido}/convocatoria/{equipo}', [ConvocatoriaController::class, 'update'])->name('convocatorias.update');
    Route::get('/partidos/{partido}/alineacion/{equipo}', [ConvocatoriaController::class, 'alineacion'])->name('convocatorias.alineacion');
    Route::get('/mis-pagos', [PagoPartidoController::class, 'misPagos'])->name('pagos.mis-pagos');
    Route::get('/partidos/{partido}/comprobante', [PagoPartidoController::class, 'create'])->name('pagos.comprobante.create');
    Route::post('/partidos/{partido}/comprobante', [PagoPartidoController::class, 'store'])->name('pagos.comprobante.store');
    //Tarjetas

    Route::post('/partidos/{partido}/tarjetas', [TarjetaController::class, 'store'])->name('tarjetas.store');
    Route::delete('/tarjetas/{tarjeta}', [TarjetaController::class, 'destroy'])->name('tarjetas.destroy');

    // Eportacion de datos
    Route::get('/pagos/exportar/excel', [PagoPartidoController::class, 'exportarExcel'])->name('pagos.export.excel');
Route::get('/pagos/exportar/pdf', [PagoPartidoController::class, 'exportarPdf'])->name('pagos.export.pdf');

    // Goles
    Route::post('/partidos/{partido}/goles', [GolController::class, 'store'])->name('goles.store');
    Route::delete('/goles/{gol}', [GolController::class, 'destroy'])->name('goles.destroy');

    // Árbitros (invitación por link)
    Route::get('/arbitros', [ArbitroController::class, 'index'])->name('arbitros.index');
    Route::post('/arbitros/invitar', [ArbitroController::class, 'invitar'])->name('arbitros.invitar');

    // Fixture automático
    Route::get('/fixture/generar', [FixtureController::class, 'create'])->name('fixtures.create');
    Route::post('/fixture/generar', [FixtureController::class, 'store'])->name('fixtures.store');
    // Route::get('/fixture', [FixtureController::class, 'index'])->name('partidos.index');

    // Tablas
    Route::get('/posiciones', [TablaController::class, 'posiciones'])->name('tablas.posiciones');
    Route::get('/goleadores', [TablaController::class, 'goleadores'])->name('tablas.goleadores');


// Configuración (panel de administración)
Route::get('/configuracion', [ConfiguracionController::class, 'index'])->name('configuracion.index');
Route::get('/configuracion/campeonato', [CampeonatoController::class, 'edit'])->name('campeonatos.edit');
Route::put('/configuracion/campeonato', [CampeonatoController::class, 'update'])->name('campeonatos.update');
Route::get('/jugadores', [JugadorController::class, 'index'])->name('jugadores.index');

// Canchas (CRUD, solo admin)
Route::get('/canchas', [CanchaController::class, 'index'])->name('canchas.index');
Route::get('/canchas/crear', [CanchaController::class, 'create'])->name('canchas.create');
Route::post('/canchas', [CanchaController::class, 'store'])->name('canchas.store');
Route::get('/canchas/{cancha}/editar', [CanchaController::class, 'edit'])->name('canchas.edit');
Route::put('/canchas/{cancha}', [CanchaController::class, 'update'])->name('canchas.update');
Route::delete('/canchas/{cancha}', [CanchaController::class, 'destroy'])->name('canchas.destroy');

// Torneos (CRUD, solo admin)
Route::get('/torneos', [TorneoController::class, 'index'])->name('torneos.index');
Route::get('/torneos/crear', [TorneoController::class, 'create'])->name('torneos.create');
Route::post('/torneos', [TorneoController::class, 'store'])->name('torneos.store');
Route::get('/torneos/{torneo}/editar', [TorneoController::class, 'edit'])->name('torneos.edit');
Route::put('/torneos/{torneo}', [TorneoController::class, 'update'])->name('torneos.update');
Route::delete('/torneos/{torneo}', [TorneoController::class, 'destroy'])->name('torneos.destroy');

Route::get('/jugadores/{jugador}/editar', [JugadorController::class, 'edit'])->name('jugadores.edit');
Route::put('/jugadores/{jugador}', [JugadorController::class, 'update'])->name('jugadores.update');
Route::delete('/jugadores/{jugador}', [JugadorController::class, 'destroy'])->name('jugadores.destroy');
Route::post('/equipos/{equipo}/unirme', [JugadorController::class, 'unirme'])->name('jugadores.unirme');
});

// Pichangas semanales: cualquier usuario logueado, no requiere ser parte del campeonato.
Route::middleware('auth')->group(function () {
    Route::get('/pichangas', [PichangaController::class, 'index'])->name('pichangas.index');
    Route::get('/pichangas/crear', [PichangaController::class, 'create'])->name('pichangas.create');
    Route::post('/pichangas', [PichangaController::class, 'store'])->name('pichangas.store');
    Route::get('/pichangas/unirse/{token}', [PichangaController::class, 'unirseForm'])->name('pichangas.unirse');
    Route::post('/pichangas/unirse/{token}', [PichangaController::class, 'unirse'])->name('pichangas.unirse.store');
    Route::get('/pichangas/{pichanga}', [PichangaController::class, 'show'])->name('pichangas.show');
    Route::get('/pichangas/{pichanga}/editar', [PichangaController::class, 'edit'])->name('pichangas.edit');
    Route::put('/pichangas/{pichanga}', [PichangaController::class, 'update'])->name('pichangas.update');
    Route::delete('/pichangas/{pichanga}/miembros/{user}', [PichangaController::class, 'quitarMiembro'])->name('pichangas.miembros.destroy');

    Route::get('/pichangas/fechas/{fecha}', [PichangaFechaController::class, 'show'])->name('pichangas.fechas.show');
    Route::post('/pichangas/fechas/{fecha}/voy', [PichangaFechaController::class, 'voy'])->name('pichangas.fechas.voy');
    Route::post('/pichangas/fechas/{fecha}/no-voy', [PichangaFechaController::class, 'noVoy'])->name('pichangas.fechas.no-voy');
    Route::post('/pichangas/fechas/{fecha}/sortear', [PichangaFechaController::class, 'sortear'])->name('pichangas.fechas.sortear');
    Route::put('/pichangas/fechas/{fecha}/planilla', [PichangaFechaController::class, 'planilla'])->name('pichangas.fechas.planilla');
    Route::post('/pichangas/fechas/{fecha}/estado', [PichangaFechaController::class, 'estado'])->name('pichangas.fechas.estado');
    Route::post('/pichangas/fechas/{fecha}/comprobante', [PichangaPagoController::class, 'store'])->name('pichangas.comprobante.store');
    Route::get('/pichangas/asistencias/{asistencia}/comprobante', [PichangaPagoController::class, 'show'])->name('pichangas.comprobante.show');
});

// Página pública de un campeonato (después de /campeonatos/crear para no capturarla)
Route::get('/campeonatos/{liga}', [CampeonatoPublicoController::class, 'show'])->name('campeonatos.publico');

require __DIR__.'/auth.php';
