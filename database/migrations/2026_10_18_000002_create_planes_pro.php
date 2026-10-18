<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Plan de cada campeonato: Gratis (hasta 8 equipos) o Pro (pago anual del admin).
    public function up(): void
    {
        Schema::table('ligas', function (Blueprint $table) {
            $table->dateTime('pro_hasta')->nullable()->after('publica'); // null o pasado = Gratis
        });

        // Pagos del plan Pro (Mercado Pago o activación manual del super admin).
        Schema::create('pagos_pro', function (Blueprint $table) {
            $table->id();
            $table->foreignId('liga_id')->constrained('ligas')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // quién pagó / activó
            $table->enum('medio', ['mercadopago', 'manual']);
            $table->enum('estado', ['pendiente', 'aprobado', 'rechazado'])->default('pendiente');
            $table->unsignedInteger('monto');                        // CLP
            $table->string('mp_preferencia_id', 100)->nullable();
            $table->string('mp_pago_id', 50)->nullable()->unique();  // evita activar dos veces el mismo pago
            $table->dateTime('pagado_en')->nullable();
            $table->dateTime('pro_desde')->nullable();               // período que cubrió este pago
            $table->dateTime('pro_hasta')->nullable();
            $table->string('nota', 255)->nullable();
            $table->timestamps();
        });

        // Ajustes que el super admin cambia sin tocar código (precio del Pro, etc.).
        Schema::create('ajustes', function (Blueprint $table) {
            $table->string('clave', 60)->primary();
            $table->string('valor', 255);
            $table->timestamps();
        });

        DB::table('ajustes')->insert([
            'clave' => 'precio_pro_anual',
            'valor' => '29990',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('ajustes');
        Schema::dropIfExists('pagos_pro');

        Schema::table('ligas', function (Blueprint $table) {
            $table->dropColumn('pro_hasta');
        });
    }
};
