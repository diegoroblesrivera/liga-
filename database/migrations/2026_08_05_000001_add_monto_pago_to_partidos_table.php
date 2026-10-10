<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partidos', function (Blueprint $table) {
            // Monto que debe pagar CADA equipo por este partido (null = sin pago asignado).
            $table->decimal('monto_pago', 10, 2)->nullable()->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('partidos', function (Blueprint $table) {
            $table->dropColumn('monto_pago');
        });
    }
};
