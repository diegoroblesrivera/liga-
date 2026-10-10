<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ligas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120);
            $table->string('slug', 80)->unique();
            $table->string('descripcion', 255)->nullable();
            $table->string('logo', 255)->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('activa')->default(true);   // false = suspendida por super admin
            $table->boolean('publica')->default(true);  // true = aparece en el home
            $table->timestamps();
        });

        // Liga 1 = la liga original. Las tablas raíz usan liga_id DEFAULT 1,
        // así que debe existir antes de agregar esas columnas.
        DB::table('ligas')->insert([
            'id' => 1,
            'nombre' => 'Liga de los Miercoles',
            'slug' => 'liga-miercoles',
            'owner_id' => DB::table('users')->where('id', 1)->exists() ? 1 : null,
            'activa' => true,
            'publica' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('ultima_liga_id')->references('id')->on('ligas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['ultima_liga_id']);
        });

        Schema::dropIfExists('ligas');
    }
};
