<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // partidos, goles, tarjetas, convocatorias, pagos_partidos y equipo_torneo
    // heredan la liga por relación: no llevan liga_id.
    // DEFAULT 1 mantiene compatible el código actual (aún no asigna liga).
    public function up(): void
    {
        foreach (['torneos', 'canchas'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->foreignId('liga_id')->default(1)->after('id')->constrained('ligas');
            });
        }

        // Nombre de equipo único POR LIGA, ya no global.
        Schema::table('equipos', function (Blueprint $table) {
            $table->unsignedBigInteger('liga_id')->default(1)->after('id');
            $table->unique(['liga_id', 'nombre'], 'equipos_liga_nombre_unique');
            $table->dropUnique('equipos_nombre_unique');
            $table->foreign('liga_id')->references('id')->on('ligas');
        });
    }

    public function down(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->dropForeign(['liga_id']);
            $table->unique('nombre', 'equipos_nombre_unique');
            $table->dropUnique('equipos_liga_nombre_unique');
            $table->dropColumn('liga_id');
        });

        foreach (['torneos', 'canchas'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropConstrainedForeignId('liga_id');
            });
        }
    }
};
