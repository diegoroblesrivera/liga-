<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partido_id')->constrained('partidos')->cascadeOnDelete();
            $table->foreignId('jugador_id')->constrained('jugadores');
            $table->foreignId('equipo_id')->constrained('equipos'); // equipo al que se acredita
            $table->unsignedSmallInteger('minuto')->nullable();
            $table->enum('tipo', ['normal', 'penal', 'autogol'])->default('normal');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goles');
    }
};
