<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Secciones del campeonato: solo para quien participa en algún torneo
// (admin, DT, jugador o árbitro). Los usuarios solo de pichangas vuelven al inicio.
class EnsureParticipaEnTorneos
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->participaEnTorneos()) {
            return redirect()->route('dashboard')
                ->with('status', 'Esa sección es solo para quienes participan en un torneo.');
        }

        return $next($request);
    }
}
