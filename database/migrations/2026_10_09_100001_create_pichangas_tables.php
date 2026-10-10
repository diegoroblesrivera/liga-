<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Módulo de pichangas semanales: independiente de ligas/equipos/torneos,
    // solo comparte la tabla users (mismo login).
    public function up(): void
    {
        // El grupo: "los martes 21:00 en tal cancha".
        Schema::create('pichangas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120);
            $table->string('descripcion', 255)->nullable();
            $table->string('lugar', 200)->nullable();
            $table->unsignedTinyInteger('dia_semana');          // 0 = domingo ... 6 = sábado (Carbon)
            $table->time('hora');
            $table->unsignedSmallInteger('duracion_min')->default(60);
            $table->unsignedSmallInteger('cupo')->default(14);  // confirmados; el resto queda en espera
            $table->unsignedInteger('precio')->nullable();      // valor total de la cancha, se divide entre confirmados
            $table->foreignId('organizador_id')->nullable()->constrained('users')->nullOnDelete();
            $table->char('token', 40)->unique();                // link de invitación reutilizable
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });

        Schema::create('pichanga_user', function (Blueprint $table) {
            $table->foreignId('pichanga_id')->constrained('pichangas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('rol', ['organizador', 'jugador'])->default('jugador');
            $table->timestamps();
            $table->primary(['pichanga_id', 'user_id']);
        });

        // Cada semana concreta de la pichanga.
        Schema::create('pichanga_fechas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pichanga_id')->constrained('pichangas')->cascadeOnDelete();
            $table->date('fecha');
            $table->time('hora');
            $table->enum('estado', ['abierta', 'cerrada', 'jugada', 'cancelada'])->default('abierta');
            $table->unsignedTinyInteger('goles_a')->nullable();
            $table->unsignedTinyInteger('goles_b')->nullable();
            $table->timestamps();
            $table->unique(['pichanga_id', 'fecha']);
        });

        // Quién va cada semana.
        Schema::create('pichanga_asistencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pichanga_fecha_id')->constrained('pichanga_fechas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('estado', ['confirmado', 'espera', 'baja']);
            $table->dateTime('anotado_en');                      // orden de llegada (para la lista de espera)
            $table->enum('equipo', ['A', 'B'])->nullable();
            $table->boolean('pagado')->default(false);
            $table->unsignedTinyInteger('goles')->default(0);
            $table->timestamps();
            $table->unique(['pichanga_fecha_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pichanga_asistencias');
        Schema::dropIfExists('pichanga_fechas');
        Schema::dropIfExists('pichanga_user');
        Schema::dropIfExists('pichangas');
    }
};
