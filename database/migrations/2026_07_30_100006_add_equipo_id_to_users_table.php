<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // La tabla users la crea la migración por defecto de Laravel.
        // Aquí solo agregamos el vínculo del DT con el equipo que dirige.
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('equipo_id')->nullable()->after('password')
                  ->constrained('equipos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['equipo_id']);
            $table->dropColumn('equipo_id');
        });
    }
};
