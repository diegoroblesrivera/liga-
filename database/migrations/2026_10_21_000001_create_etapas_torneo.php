<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Formatos de torneo: 1 o 2 etapas. Cada etapa es liga (todos contra todos), grupos o
// eliminación directa. Los torneos anteriores no tienen etapas: son una liga simple.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etapas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('torneo_id')->constrained('torneos')->cascadeOnDelete();
            $table->unsignedTinyInteger('orden');                 // 1 o 2
            $table->enum('tipo', ['liga', 'grupos', 'eliminacion']);
            $table->boolean('ida_vuelta')->default(false);        // liga y grupos
            $table->unsignedTinyInteger('grupos')->nullable();    // cantidad de grupos
            $table->unsignedTinyInteger('clasifican')->nullable(); // liga: total que pasan; grupos: por grupo; eliminación: equipos de la llave
            $table->boolean('tercer_lugar')->default(false);      // eliminación
            $table->dateTime('generada_en')->nullable();          // la 2ª etapa se genera al terminar la 1ª
            $table->timestamps();

            $table->unique(['torneo_id', 'orden']);
        });

        // Equipos de cada etapa, con su grupo (A, B...) o su lugar en la llave.
        Schema::create('etapa_equipo', function (Blueprint $table) {
            $table->foreignId('etapa_id')->constrained('etapas')->cascadeOnDelete();
            $table->foreignId('equipo_id')->constrained('equipos')->cascadeOnDelete();
            $table->char('grupo', 1)->nullable();
            $table->unsignedTinyInteger('siembra')->nullable();

            $table->primary(['etapa_id', 'equipo_id']);
        });

        Schema::table('partidos', function (Blueprint $table) {
            $table->foreignId('etapa_id')->nullable()->after('torneo_id')->constrained('etapas')->cascadeOnDelete();
            $table->char('grupo', 1)->nullable()->after('etapa_id');
            $table->string('ronda', 20)->nullable()->after('grupo');              // octavos, cuartos, semifinal, final, tercer_lugar
            $table->unsignedTinyInteger('llave')->nullable()->after('ronda');     // posición dentro de la ronda (1..n)
            $table->unsignedTinyInteger('penales_local')->nullable()->after('goles_visita');
            $table->unsignedTinyInteger('penales_visita')->nullable()->after('penales_local');
        });

        // Días, hora y partidos por día elegidos al crear: se reusan para la 2ª etapa y las rondas siguientes.
        Schema::table('torneos', function (Blueprint $table) {
            $table->json('programacion')->nullable()->after('fecha_fin');
        });
    }

    public function down(): void
    {
        Schema::table('torneos', function (Blueprint $table) {
            $table->dropColumn('programacion');
        });
        Schema::table('partidos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('etapa_id');
            $table->dropColumn(['grupo', 'ronda', 'llave', 'penales_local', 'penales_visita']);
        });
        Schema::dropIfExists('etapa_equipo');
        Schema::dropIfExists('etapas');
    }
};
