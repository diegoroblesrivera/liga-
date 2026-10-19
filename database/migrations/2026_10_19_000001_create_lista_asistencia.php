<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Lista de asistencia por QR: opcional por campeonato. Un QR por fecha (torneo + número
// de fecha) que se escanea en la cancha o se imprime para la mesa de la organización.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ligas', function (Blueprint $table) {
            $table->boolean('pedir_lista')->default(false)->after('pro_hasta');
            // Si está activa, quien no marcó asistencia no puede aparecer en la planilla.
            $table->boolean('lista_obligatoria')->default(false)->after('pedir_lista');
        });

        Schema::create('listas_fecha', function (Blueprint $table) {
            $table->id();
            $table->foreignId('torneo_id')->constrained('torneos')->cascadeOnDelete();
            $table->unsignedSmallInteger('numero_fecha');
            $table->string('token', 40)->unique();
            $table->timestamps();

            $table->unique(['torneo_id', 'numero_fecha']);
        });

        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partido_id')->constrained('partidos')->cascadeOnDelete();
            $table->foreignId('jugador_id')->constrained('jugadores')->cascadeOnDelete();
            $table->enum('metodo', ['qr', 'manual'])->default('qr');
            $table->foreignId('marcado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['partido_id', 'jugador_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencias');
        Schema::dropIfExists('listas_fecha');

        Schema::table('ligas', function (Blueprint $table) {
            $table->dropColumn(['pedir_lista', 'lista_obligatoria']);
        });
    }
};
