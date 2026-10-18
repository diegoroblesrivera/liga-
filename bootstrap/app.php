<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'torneos' => \App\Http\Middleware\EnsureParticipaEnTorneos::class,
            'superadmin' => \App\Http\Middleware\EnsureSuperAdmin::class,
        ]);

        // Mercado Pago llama a esta ruta desde sus servidores: no trae token CSRF.
        // Su contenido no se usa tal cual: el pago se verifica consultando a Mercado Pago.
        $middleware->validateCsrfTokens(except: ['webhooks/mercadopago']);

        // Campeonato actual del usuario (filtra equipos, torneos, partidos...).
        // Debe correr ANTES de SubstituteBindings: si no, /equipos/{equipo} buscaría
        // el equipo sin filtro y se podría abrir uno de otro campeonato.
        $middleware->web(append: [
            \App\Http\Middleware\ResolverLigaActual::class,
        ]);
        $middleware->prependToPriorityList(
            before: \Illuminate\Routing\Middleware\SubstituteBindings::class,
            prepend: \App\Http\Middleware\ResolverLigaActual::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();

// En el hosting la carpeta pública es ../public_html; en local se usa public/.
$publicHtml = dirname(__DIR__, 2).'/public_html';
if (is_dir($publicHtml)) {
    $app->usePublicPath($publicHtml);
}

return $app;
