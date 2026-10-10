<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_id')->constrained('equipos')->cascadeOnDelete();
            $table->enum('tipo', ['dt', 'jugador']);
            $table->char('token', 40)->unique();
            $table->string('email', 180)->nullable();          // opcional: invitación dirigida
            $table->boolean('reutilizable')->default(false);   // dt=false (un uso) · jugador=true
            $table->unsignedInteger('usos')->default(0);
            $table->dateTime('expira_en')->nullable();
            $table->foreignId('creada_por')->nullable()        // admin o dt que generó el link
                  ->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitaciones');
    }
};
