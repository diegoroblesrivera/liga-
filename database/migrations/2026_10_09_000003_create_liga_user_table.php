<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // owner / admin / arbitro por liga. DT y jugador NO van aquí: su liga sale de su equipo.
        Schema::create('liga_user', function (Blueprint $table) {
            $table->foreignId('liga_id')->constrained('ligas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('rol', ['owner', 'admin', 'arbitro']);
            $table->timestamps();
            $table->primary(['liga_id', 'user_id', 'rol']);
        });

        // Admins (role_id 1) y árbitros (role_id 4) actuales pasan a la liga 1.
        foreach ([1 => 'admin', 4 => 'arbitro'] as $roleId => $rol) {
            $userIds = DB::table('role_user')->where('role_id', $roleId)->pluck('user_id');

            foreach ($userIds as $userId) {
                DB::table('liga_user')->insert([
                    'liga_id' => 1,
                    'user_id' => $userId,
                    'rol' => $userId == 1 && $rol === 'admin' ? 'owner' : $rol,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        DB::table('users')->where('id', 1)->update(['is_super_admin' => true]);
    }

    public function down(): void
    {
        Schema::dropIfExists('liga_user');
    }
};
