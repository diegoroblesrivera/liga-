<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Cómo se cobra la pichanga: dividiendo el valor de la cancha entre los confirmados
// (lo de siempre) o con una cuota fija por persona, sin importar cuántos se anoten.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pichangas', function (Blueprint $table) {
            $table->enum('cobro', ['dividido', 'cuota'])->default('dividido')->after('precio');
            $table->unsignedInteger('cuota')->nullable()->after('cobro');
        });
    }

    public function down(): void
    {
        Schema::table('pichangas', function (Blueprint $table) {
            $table->dropColumn(['cobro', 'cuota']);
        });
    }
};
