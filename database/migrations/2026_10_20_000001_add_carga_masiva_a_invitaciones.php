<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Carga masiva por Excel: las invitaciones guardan nombre y teléfono del invitado, la
// ficha de jugador ya creada que van a reclamar y cuándo se envió el correo.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invitaciones', function (Blueprint $table) {
            $table->string('nombre', 120)->nullable()->after('email');
            $table->string('telefono', 30)->nullable()->after('nombre');
            $table->foreignId('jugador_id')->nullable()->after('equipo_id')->constrained('jugadores')->cascadeOnDelete();
            $table->dateTime('enviada_en')->nullable()->after('expira_en');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('telefono', 30)->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('invitaciones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('jugador_id');
            $table->dropColumn(['nombre', 'telefono', 'enviada_en']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('telefono');
        });
    }
};
