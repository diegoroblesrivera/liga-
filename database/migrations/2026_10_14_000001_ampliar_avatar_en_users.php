<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Google puede entregar la URL de la foto de perfil con más de 1.000 caracteres;
    // con varchar(255) el login con Google fallaba (error 500) para esos usuarios.
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('avatar')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar', 255)->nullable()->change();
        });
    }
};
