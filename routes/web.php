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
use App\Http\Controllers\PichangaInvitadoController;
use App\Http\Controllers\CampeonatoController;
use App\Http\Controllers\CampeonatoPublicoController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\WebhookMercadoPagoController;
use App\Http\Controllers\AdminCampeonatosController;
use App\Http\Controllers\ListaController;
use App\Http\Controllers\LogroController;
use App\Http\Controllers\AdminMetricasController;
use App\Http\Controllers\CargaMasivaController;


// Registro por invitación a un equipo (público)
Route::get('/invitacion/{token}', [InscripcionController::class, 'show'])->name('invitacion.show');
Route::post('/invitacion/{token}', [InscripcionController::class, 'store'])->middleware('guest')->name('invitacion.store');
Route::post('/invitacion/{token}/aceptar', [InscripcionController::class, 'aceptar'])->middleware('auth')->name('invitacion.aceptar');


Route::get('/',[HomeController::class, 'index'])->name('home');
Route::get('/precios', [HomeController::class, 'precios'])->name('precios');

// Política de privacidad (pública)
Route::get('/privacidad', fn () => view('legal.privacidad', [
    'actualizada' => '16 de octubre de 2026',
    'correo' => config('app.correo_privacidad'),
    'dominio' => parse_url(config('app.url'), PHP_URL_HOST),
]))->name('privacidad');

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

    // Regreso desde Mercado Pago tras pagar el Pro (verifica el pago con Mercado Pago)
    Route::get('/configuracion/plan/retorno', [PlanController::class, 'retorno'])->name('campeonatos.plan.retorno');

    // El jugador escanea el QR de la lista (puede estar en otro campeonato como actual)
    Route::get('/lista/{token}', [ListaController::class, 'escanear'])->name('lista.escanear');

    // Mis logros (campeonatos y pichangas)
    Route::get('/mis-logros', [LogroController::class, 'mios'])->name('logros.mios');
});

// Campeonato: solo quienes participan en algún torneo (admin, DT, jugador o árbitro).
Route::middleware(['auth', 'torneos'])->group(function () {
    // Equipos
    Route::get('/equipos', [EquipoController::class, 'index'])->name('equipos.index');
    Route::get('/equipos/crear', [EquipoController::class, 'create'])->name('equipos.create');
    // Carga masiva por Excel: equipos con su DT (organizador) y plantel (DT)
    Route::get('/equipos/importar', [CargaMasivaController::class, 'equipos'])->name('carga.equipos');
    Route::get('/equipos/importar/plantilla', [CargaMasivaController::class, 'plantillaEquipos'])->name('carga.equipos.plantilla');
    Route::post('/equipos/importar', [CargaMasivaController::class, 'importarEquipos'])->name('carga.equipos.importar');
    Route::get('/equipos/{equipo}/importar-jugadores', [CargaMasivaController::class, 'jugadores'])->name('carga.jugadores');
    Route::get('/equipos/{equipo}/importar-jugadores/plantilla', [CargaMasivaController::class, 'plantillaJugadores'])->name('carga.jugadores.plantilla');
    Route::post('/equipos/{equipo}/importar-jugadores', [CargaMasivaController::class, 'importarJugadores'])->name('carga.jugadores.importar');
    Route::post('/invitaciones/{invitacion}/reenviar', [CargaMasivaController::class, 'reenviar'])->name('invitaciones.reenviar');
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
    Route::delete('/partidos/{partido}', [PartidoController::class, 'destroy'])->name('partidos.destroy');
    Route::delete('/torneos/{torneo}/fechas/{numero}', [PartidoController::class, 'destroyFecha'])
        ->whereNumber('numero')->name('partidos.fechas.destroy');

    // Lista de asistencia por QR (un QR por fecha; también para imprimir)
    Route::get('/torneos/{torneo}/fechas/{numero}/lista', [ListaController::class, 'fecha'])->whereNumber('numero')->name('lista.fecha');
    Route::get('/torneos/{torneo}/fechas/{numero}/lista/imprimir', [ListaController::class, 'imprimir'])->whereNumber('numero')->name('lista.imprimir');
    Route::post('/torneos/{torneo}/fechas/{numero}/lista/regenerar', [ListaController::class, 'regenerar'])->whereNumber('numero')->name('lista.regenerar');
    Route::post('/partidos/{partido}/asistencia', [ListaController::class, 'marcar'])->name('lista.marcar');
    Route::get('/jugadores/{jugador}/logros', [LogroController::class, 'jugador'])->name('jugadores.logros');
    Route::delete('/partidos/{partido}/asistencia/{jugador}', [ListaController::class, 'desmarcar'])->name('lista.desmarcar');
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
    Route::get('/pagos/{pago}/comprobante', [PagoPartidoController::class, 'verComprobante'])->name('pagos.comprobante.ver');
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
    Route::post('/torneos/{torneo}/segunda-etapa', [FixtureController::class, 'segundaEtapa'])->name('fixtures.segunda');
    // Route::get('/fixture', [FixtureController::class, 'index'])->name('partidos.index');

    // Tablas
    Route::get('/posiciones', [TablaController::class, 'posiciones'])->name('tablas.posiciones');
    Route::get('/goleadores', [TablaController::class, 'goleadores'])->name('tablas.goleadores');


// Configuración (panel de administración)
Route::get('/configuracion', [ConfiguracionController::class, 'index'])->name('configuracion.index');
Route::get('/configuracion/campeonato', [CampeonatoController::class, 'edit'])->name('campeonatos.edit');
Route::get('/configuracion/plan', [PlanController::class, 'show'])->name('campeonatos.plan');
Route::post('/configuracion/plan/pagar', [PlanController::class, 'pagar'])->name('campeonatos.plan.pagar');
Route::put('/configuracion/campeonato', [CampeonatoController::class, 'update'])->name('campeonatos.update');
Route::put('/configuracion/lista', [ListaController::class, 'configurar'])->name('campeonatos.lista');
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
Route::post('/torneos/{torneo}/cerrar', [TorneoController::class, 'cerrar'])->name('torneos.cerrar');
Route::delete('/torneos/{torneo}/cerrar', [TorneoController::class, 'reabrir'])->name('torneos.reabrir');

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
    Route::delete('/pichangas/{pichanga}', [PichangaController::class, 'destroy'])->name('pichangas.destroy');
    Route::delete('/pichangas/{pichanga}/miembros/{user}', [PichangaController::class, 'quitarMiembro'])->name('pichangas.miembros.destroy');

    Route::get('/pichangas/fechas/{fecha}', [PichangaFechaController::class, 'show'])->name('pichangas.fechas.show');
    Route::post('/pichangas/fechas/{fecha}/voy', [PichangaFechaController::class, 'voy'])->name('pichangas.fechas.voy');
    Route::post('/pichangas/fechas/{fecha}/no-voy', [PichangaFechaController::class, 'noVoy'])->name('pichangas.fechas.no-voy');
    Route::post('/pichangas/fechas/{fecha}/sortear', [PichangaFechaController::class, 'sortear'])->name('pichangas.fechas.sortear');
    Route::put('/pichangas/fechas/{fecha}/planilla', [PichangaFechaController::class, 'planilla'])->name('pichangas.fechas.planilla');
    Route::post('/pichangas/fechas/{fecha}/estado', [PichangaFechaController::class, 'estado'])->name('pichangas.fechas.estado');
    Route::post('/pichangas/fechas/{fecha}/comprobante', [PichangaPagoController::class, 'store'])->name('pichangas.comprobante.store');
    Route::post('/pichangas/fechas/{fecha}/invitados', [PichangaInvitadoController::class, 'store'])->name('pichangas.invitados.store');
    Route::delete('/pichangas/invitados/{asistencia}', [PichangaInvitadoController::class, 'destroy'])->name('pichangas.invitados.destroy');
    Route::get('/pichangas/asistencias/{asistencia}/comprobante', [PichangaPagoController::class, 'show'])->name('pichangas.comprobante.show');
});

// Avisos de pago de Mercado Pago (público; sin CSRF, ver bootstrap/app.php)
Route::post('/webhooks/mercadopago', WebhookMercadoPagoController::class)->name('webhooks.mercadopago');

// Panel interno del super admin
Route::middleware(['auth', 'superadmin'])->prefix('admin')->group(function () {
    Route::get('/metricas', [AdminMetricasController::class, 'index'])->name('admin.metricas');
    Route::get('/campeonatos', [AdminCampeonatosController::class, 'index'])->name('admin.campeonatos');
    Route::post('/campeonatos/{liga}/pro', [AdminCampeonatosController::class, 'activarPro'])->name('admin.campeonatos.pro');
    Route::delete('/campeonatos/{liga}/pro', [AdminCampeonatosController::class, 'quitarPro'])->name('admin.campeonatos.pro.quitar');
    Route::put('/precio-pro', [AdminCampeonatosController::class, 'precio'])->name('admin.precio');
});

// Página pública de un campeonato (después de /campeonatos/crear para no capturarla)
Route::get('/campeonatos/{liga}', [CampeonatoPublicoController::class, 'show'])->name('campeonatos.publico');

require __DIR__.'/auth.php';
