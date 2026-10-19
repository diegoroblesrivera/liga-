<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Recalcula todos los logros desde los datos (para la historia o tras corregir algo a mano).
Artisan::command('logros:recalcular {--liga= : Solo este campeonato (id)}', function (\App\Services\Logros $logros) {
    $ligas = \Illuminate\Support\Facades\DB::table('ligas')
        ->when($this->option('liga'), fn ($q, $id) => $q->where('id', $id))
        ->pluck('nombre', 'id');

    foreach ($ligas as $id => $nombre) {
        $logros->recalcularLiga($id);
        $this->line("✓ {$nombre}");
    }

    if (! $this->option('liga')) {
        $usuarios = \Illuminate\Support\Facades\DB::table('pichanga_asistencias')->whereNotNull('user_id')->distinct()->pluck('user_id');
        $usuarios->each(fn ($id) => $logros->recalcularUsuarioPichangas($id));
        $this->line("✓ Pichangas ({$usuarios->count()} jugadores)");
    }

    $this->info('Logros otorgados: '.\App\Models\Logro::count());
})->purpose('Recalcula los logros (insignias) de jugadores y pichangas');
