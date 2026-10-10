<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modalidades', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 60)->unique();
            $table->unsignedSmallInteger('max_jugadores'); // cupo de inscritos por equipo
        });

        DB::table('modalidades')->insert([
            ['nombre' => 'Baby fútbol', 'max_jugadores' => 8],
            ['nombre' => 'Futbolito',   'max_jugadores' => 10],
            ['nombre' => 'Fútbol 11',   'max_jugadores' => 18],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('modalidades');
    }
};
