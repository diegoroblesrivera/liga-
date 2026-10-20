<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

// Último acceso del usuario (como mucho una escritura cada 5 minutos) y su día activo.
class RegistrarActividad
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && (! $user->ultimo_acceso || $user->ultimo_acceso->lt(now()->subMinutes(5)))) {
            $nuevoDia = ! $user->ultimo_acceso || ! $user->ultimo_acceso->isToday();

            // Directo a la tabla: sin tocar updated_at ni disparar eventos del modelo.
            DB::table('users')->where('id', $user->id)->update(['ultimo_acceso' => now()]);
            $user->ultimo_acceso = now();

            if ($nuevoDia) {
                DB::table('actividad_diaria')->insertOrIgnore(['fecha' => today()->toDateString(), 'user_id' => $user->id]);
            }
        }

        return $next($request);
    }
}
