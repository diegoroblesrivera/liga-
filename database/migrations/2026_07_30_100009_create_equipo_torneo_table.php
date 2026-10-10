<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipo_torneo', function (Blueprint $table) {
            $table->foreignId('equipo_id')->constrained('equipos')->cascadeOnDelete();
            $table->foreignId('torneo_id')->constrained('torneos')->cascadeOnDelete();
            $table->primary(['equipo_id', 'torneo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipo_torneo');
    }
};
