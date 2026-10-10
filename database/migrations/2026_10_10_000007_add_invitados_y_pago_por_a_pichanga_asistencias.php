<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Invitados sin cuenta (los lleva un jugador; no ocupan cupo) y pago por otros
    // (un comprobante puede cubrir a varios: queda registrado quién pagó).
    public function up(): void
    {
        Schema::table('pichanga_asistencias', function (Blueprint $table) {
            // El invitado no tiene usuario.
            $table->unsignedBigInteger('user_id')->nullable()->change();

            $table->foreignId('invitado_por')->nullable()->after('user_id')
                ->constrained('users')->cascadeOnDelete();
            $table->string('invitado_nombre', 120)->nullable()->after('invitado_por');
            $table->string('invitado_run', 12)->nullable()->after('invitado_nombre');

            $table->foreignId('pagado_por')->nullable()->after('pagado')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pichanga_asistencias', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pagado_por');
            $table->dropConstrainedForeignId('invitado_por');
            $table->dropColumn(['invitado_nombre', 'invitado_run']);
        });
    }
};
