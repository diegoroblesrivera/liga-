<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jugadores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_id')->constrained('equipos')->cascadeOnDelete();
            // Cuenta de acceso enlazada (opcional): un jugador puede existir sin login.
            $table->foreignId('user_id')->nullable()->unique()
                  ->constrained('users')->nullOnDelete();
            $table->string('nombre', 120);
            $table->string('foto', 255)->nullable(); // foto del jugador (ruta/URL)
            $table->unsignedSmallInteger('dorsal')->nullable();
            $table->string('posicion', 40)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jugadores');
    }
};
