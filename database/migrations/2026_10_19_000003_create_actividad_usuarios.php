<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Métricas del panel: último acceso de cada usuario y un registro de un día por usuario
// activo (para usuarios activos por semana/mes sin guardar cada visita).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dateTime('ultimo_acceso')->nullable()->index();
        });

        Schema::create('actividad_diaria', function (Blueprint $table) {
            $table->date('fecha');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->primary(['fecha', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actividad_diaria');

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['ultimo_acceso']);
            $table->dropColumn('ultimo_acceso');
        });
    }
};
