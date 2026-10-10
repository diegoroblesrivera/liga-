<?php

namespace App\Http\Middleware;

use App\Models\Liga;
use App\Support\LigaActual;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Fija el campeonato actual del usuario: el elegido en la sesión, si no el último
// que usó, si no el primero donde participa. Invitados y usuarios solo de pichangas: ninguno.
class ResolverLigaActual
{
    public function handle(Request $request, Closure $next): Response
    {
        LigaActual::fijar(null);

        $user = $request->user();
        $user?->olvidarCacheLigas();
        $ids = $user?->ligasIds() ?? [];

        if ($ids !== []) {
            $id = collect([$request->session()->get('liga_id'), $user->ultima_liga_id])
                ->map(fn ($id) => (int) $id)
                ->first(fn ($id) => in_array($id, $ids, true)) ?? $ids[0];

            LigaActual::fijar(Liga::find($id));
            $request->session()->put('liga_id', $id);
        }

        return $next($request);
    }
}
