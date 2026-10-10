<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // La columna se agregó a mano en el hosting y nunca tuvo migración: en una
    // instalación nueva, crear un equipo fallaba. Donde ya existe, no se toca.
    public function up(): void
    {
        if (! Schema::hasColumn('equipos', 'color_camiseta')) {
            Schema::table('equipos', function (Blueprint $table) {
                $table->string('color_camiseta', 30)->nullable()->after('foto');
            });
        }
    }

    public function down(): void
    {
        // Sin down: en producción la columna existía antes de esta migración.
    }
};
