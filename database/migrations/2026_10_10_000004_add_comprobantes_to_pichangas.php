<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Cada jugador sube su comprobante de transferencia (queda pagado al subirlo).
    // Los archivos van en storage/app/private: solo los ven el dueño y el organizador.
    public function up(): void
    {
        Schema::table('pichangas', function (Blueprint $table) {
            $table->text('datos_transferencia')->nullable()->after('pago_modo'); // banco, cuenta, RUT, correo
        });

        Schema::table('pichanga_asistencias', function (Blueprint $table) {
            $table->string('comprobante_path', 255)->nullable()->after('pagado');
            $table->dateTime('comprobante_subido_en')->nullable()->after('comprobante_path');
        });
    }

    public function down(): void
    {
        Schema::table('pichanga_asistencias', function (Blueprint $table) {
            $table->dropColumn(['comprobante_path', 'comprobante_subido_en']);
        });

        Schema::table('pichangas', function (Blueprint $table) {
            $table->dropColumn('datos_transferencia');
        });
    }
};
