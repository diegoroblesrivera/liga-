<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jugadores', function (Blueprint $table) {
            $table->boolean('es_dt')->default(false)->after('activo');
        });

        // Es DT el jugador cuyo usuario dirige ese mismo equipo.
        DB::statement(<<<'SQL'
UPDATE jugadores SET es_dt = 1
WHERE EXISTS (
    SELECT 1 FROM users u
    WHERE u.id = jugadores.user_id AND u.equipo_id = jugadores.equipo_id
)
SQL);

        // Un usuario puede jugar en varias ligas: único por (usuario, equipo).
        Schema::table('jugadores', function (Blueprint $table) {
            $table->unique(['user_id', 'equipo_id'], 'jugadores_user_equipo_unique');
            $table->dropUnique('jugadores_user_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('jugadores', function (Blueprint $table) {
            $table->unique('user_id', 'jugadores_user_id_unique');
            $table->dropUnique('jugadores_user_equipo_unique');
            $table->dropColumn('es_dt');
        });
    }
};
