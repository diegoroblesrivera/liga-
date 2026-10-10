<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('torneo_id')->constrained('torneos')->cascadeOnDelete();
            $table->unsignedSmallInteger('numero_fecha'); // la jornada / fecha
            $table->foreignId('equipo_local_id')->constrained('equipos');
            $table->foreignId('equipo_visita_id')->constrained('equipos');
            $table->foreignId('cancha_id')->nullable()->constrained('canchas')->nullOnDelete();
            $table->foreignId('arbitro_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('fecha_hora')->nullable();
            $table->unsignedTinyInteger('goles_local')->nullable();  // NULL = aún no se juega
            $table->unsignedTinyInteger('goles_visita')->nullable();
            $table->enum('estado', ['programado', 'en_juego', 'finalizado', 'suspendido'])
                  ->default('programado');
            $table->dateTime('iniciado_en')->nullable();   // auditoría
            $table->dateTime('finalizado_en')->nullable(); // auditoría
            $table->timestamps();

            $table->index(['torneo_id', 'numero_fecha']);
        });

        // Requiere MySQL 8.0.16+ para que el CHECK se aplique realmente.
        DB::statement(
            'ALTER TABLE partidos ADD CONSTRAINT chk_equipos_distintos '.
            'CHECK (equipo_local_id <> equipo_visita_id)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('partidos');
    }
};
