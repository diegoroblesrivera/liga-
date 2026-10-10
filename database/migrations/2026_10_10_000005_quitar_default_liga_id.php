<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Limpieza de la Fase 1 multi-liga: el DEFAULT 1 solo servía para que el código
    // antiguo (de una sola liga) siguiera funcionando. Ahora cada registro toma el
    // campeonato actual; sin campeonato, mejor que la base lo rechace a que caiga en la liga 1.
    private array $tablas = ['torneos', 'canchas', 'equipos', 'invitaciones'];

    public function up(): void
    {
        foreach ($this->tablas as $tabla) {
            DB::statement("ALTER TABLE {$tabla} ALTER COLUMN liga_id DROP DEFAULT");
        }
    }

    public function down(): void
    {
        foreach ($this->tablas as $tabla) {
            DB::statement("ALTER TABLE {$tabla} ALTER COLUMN liga_id SET DEFAULT 1");
        }
    }
};
