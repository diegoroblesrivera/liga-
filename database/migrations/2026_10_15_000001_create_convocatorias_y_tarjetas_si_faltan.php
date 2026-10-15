<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Estas tablas se crearon a mano en el hosting y nunca tuvieron migración: en una
    // instalación nueva, la ficha del partido fallaba. Se replican igual que en producción
    // (mismos nombres de índices y llaves); donde ya existen, no se toca nada.
    public function up(): void
    {
        if (! Schema::hasTable('convocatorias')) {
            Schema::create('convocatorias', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('partido_id');
                $table->unsignedBigInteger('jugador_id');
                $table->boolean('es_titular')->default(true);
                $table->boolean('es_arquero')->default(false);
                $table->timestamps();

                $table->unique(['partido_id', 'jugador_id'], 'conv_partido_jugador');
                $table->index('partido_id', 'conv_partido');
                $table->foreign('jugador_id', 'conv_jugador_fk')->references('id')->on('jugadores')->cascadeOnDelete();
                $table->foreign('partido_id', 'conv_partido_fk')->references('id')->on('partidos')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('tarjetas')) {
            Schema::create('tarjetas', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('partido_id');
                $table->unsignedBigInteger('jugador_id');
                $table->unsignedBigInteger('equipo_id');
                $table->enum('tipo', ['amarilla', 'roja']);
                $table->unsignedSmallInteger('minuto')->nullable();
                $table->string('comentario', 200)->nullable();
                $table->timestamps();

                $table->index('partido_id', 'tarj_partido');
                $table->index('jugador_id', 'tarj_jugador');
                $table->index('equipo_id', 'tarj_equipo');
                $table->foreign('equipo_id', 'tarj_equipo_fk')->references('id')->on('equipos')->cascadeOnDelete();
                $table->foreign('jugador_id', 'tarj_jugador_fk')->references('id')->on('jugadores')->cascadeOnDelete();
                $table->foreign('partido_id', 'tarj_partido_fk')->references('id')->on('partidos')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Sin down: en producción estas tablas existían antes de esta migración.
    }
};
