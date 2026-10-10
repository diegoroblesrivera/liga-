<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Datos que pide la portería del recinto: RUN, patente y acompañantes.
    // Solo los ve el organizador de las pichangas donde el usuario se confirma.
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('run', 12)->nullable()->after('email');      // "12345678-5"
            $table->string('patente', 10)->nullable()->after('run');    // "BBCL12"
        });

        Schema::create('acompanantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nombre', 120);
            $table->string('run', 12);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acompanantes');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['run', 'patente']);
        });
    }
};
