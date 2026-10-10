<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Equipo rival opcional y qué parte de la cancha paga el grupo.
    // `precio` sigue siendo el valor de la cancha completa.
    public function up(): void
    {
        Schema::table('pichangas', function (Blueprint $table) {
            $table->boolean('tiene_rival')->default(false)->after('cupo');
            $table->string('rival_nombre', 120)->nullable()->after('tiene_rival');
            $table->enum('pago_modo', ['completa', 'mitad'])->default('completa')->after('precio');
        });
    }

    public function down(): void
    {
        Schema::table('pichangas', function (Blueprint $table) {
            $table->dropColumn(['tiene_rival', 'rival_nombre', 'pago_modo']);
        });
    }
};
