<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // El árbitro no pertenece a un equipo: su invitación va sin equipo_id.
        DB::statement("ALTER TABLE invitaciones MODIFY tipo ENUM('dt','jugador','arbitro') NOT NULL");
        DB::statement("ALTER TABLE invitaciones MODIFY equipo_id BIGINT UNSIGNED NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE invitaciones MODIFY equipo_id BIGINT UNSIGNED NOT NULL");
        DB::statement("ALTER TABLE invitaciones MODIFY tipo ENUM('dt','jugador') NOT NULL");
    }
};
