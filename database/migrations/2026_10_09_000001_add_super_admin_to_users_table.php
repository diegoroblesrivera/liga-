<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Fase 1 multi-liga (ver multiliga_fase1.sql). La FK de ultima_liga_id
    // se agrega en create_ligas_table, cuando ya existe la tabla ligas.
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_super_admin')->default(false)->after('password');
            $table->unsignedBigInteger('ultima_liga_id')->nullable()->after('equipo_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_super_admin', 'ultima_liga_id']);
        });
    }
};
