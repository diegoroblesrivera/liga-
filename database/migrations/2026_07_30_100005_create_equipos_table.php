<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120)->unique();
            // La modalidad define el cupo máximo de jugadores. RESTRICT por defecto.
            $table->foreignId('modalidad_id')->constrained('modalidades');
            $table->string('escudo', 255)->nullable(); // logo del equipo (ruta/URL)
            $table->string('foto', 255)->nullable();    // foto del plantel (ruta/URL)
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipos');
    }
};
