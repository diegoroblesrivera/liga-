<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos_partidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partido_id')->constrained('partidos')->cascadeOnDelete();
            $table->foreignId('equipo_id')->constrained('equipos')->cascadeOnDelete();
            $table->string('comprobante_path');
            $table->foreignId('subido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('fecha_subida')->nullable();
            $table->timestamps();

            // Un solo comprobante por equipo y por partido (se reemplaza, no se acumula).
            $table->unique(['partido_id', 'equipo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_partidos');
    }
};
