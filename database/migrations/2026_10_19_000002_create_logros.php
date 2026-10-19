<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Logros (insignias). Los de campeonato son de la ficha del jugador (jugador_id); los de
// pichangas, del usuario (user_id). "huella" identifica cada logro para no repetirlo.
return new class extends Migration
{
    public function up(): void
    {
        // El organizador cierra el torneo: ahí se entregan Campeón, Pichichi y Asistencia perfecta.
        Schema::table('torneos', function (Blueprint $table) {
            $table->dateTime('cerrado_en')->nullable()->after('fecha_fin');
        });

        Schema::create('logros', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 40);
            $table->foreignId('jugador_id')->nullable()->constrained('jugadores')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('liga_id')->nullable()->constrained('ligas')->cascadeOnDelete();
            $table->foreignId('torneo_id')->nullable()->constrained('torneos')->cascadeOnDelete();
            $table->unsignedSmallInteger('numero_fecha')->nullable();
            $table->foreignId('partido_id')->nullable()->constrained('partidos')->nullOnDelete();
            $table->string('huella', 120)->unique();
            $table->dateTime('obtenido_en');
            $table->dateTime('visto_en')->nullable(); // para avisar "¡Desbloqueaste…!"
            $table->timestamps();

            $table->index(['jugador_id', 'clave']);
            $table->index(['user_id', 'clave']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logros');

        Schema::table('torneos', function (Blueprint $table) {
            $table->dropColumn('cerrado_en');
        });
    }
};
