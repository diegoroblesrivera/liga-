<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->enum('nombre', ['admin', 'dt', 'jugador', 'arbitro'])->unique();
        });

        DB::table('roles')->insert([
            ['nombre' => 'admin'],
            ['nombre' => 'dt'],
            ['nombre' => 'jugador'],
            ['nombre' => 'arbitro'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
