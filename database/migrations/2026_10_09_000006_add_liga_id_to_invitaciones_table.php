<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invitaciones', function (Blueprint $table) {
            $table->dropForeign(['equipo_id']);
            $table->unsignedBigInteger('liga_id')->default(1)->after('id');
        });

        // Invitaciones de admin/árbitro van sin equipo.
        DB::statement("ALTER TABLE invitaciones MODIFY tipo ENUM('dt','jugador','admin','arbitro') NOT NULL");

        DB::statement(<<<'SQL'
UPDATE invitaciones i
JOIN equipos e ON e.id = i.equipo_id
SET i.liga_id = e.liga_id
SQL);

        Schema::table('invitaciones', function (Blueprint $table) {
            $table->foreign('liga_id')->references('id')->on('ligas')->cascadeOnDelete();
            $table->foreign('equipo_id')->references('id')->on('equipos')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invitaciones', function (Blueprint $table) {
            $table->dropForeign(['liga_id']);
            $table->dropColumn('liga_id');
        });

        DB::statement("ALTER TABLE invitaciones MODIFY tipo ENUM('dt','jugador','arbitro') NOT NULL");
    }
};
