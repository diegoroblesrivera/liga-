<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    // Los comprobantes de pago de los campeonatos estaban en public/uploads/comprobantes
    // con nombres predecibles: cualquiera con el enlace podía verlos (muestran datos bancarios).
    // Se mueven a storage/app/private/comprobantes-partidos/{partido}/, que solo se abre
    // a través de la app (admin del campeonato o DT del equipo que pagó).
    public function up(): void
    {
        $disco = Storage::disk('local');

        DB::table('pagos_partidos')
            ->where('comprobante_path', 'like', 'uploads/comprobantes/%')
            ->orderBy('id')
            ->get(['id', 'comprobante_path'])
            ->each(function ($pago) use ($disco) {
                $nueva = 'comprobantes-partidos/'.substr($pago->comprobante_path, strlen('uploads/comprobantes/'));
                $origen = public_path($pago->comprobante_path);

                if (is_file($origen)) {
                    $disco->put($nueva, file_get_contents($origen));
                    @unlink($origen);
                }

                DB::table('pagos_partidos')->where('id', $pago->id)->update(['comprobante_path' => $nueva]);
            });

        // Carpetas que quedaron vacías en la parte pública.
        $publica = public_path('uploads/comprobantes');
        if (is_dir($publica)) {
            foreach (File::directories($publica) as $dir) {
                if (count(File::allFiles($dir)) === 0) {
                    File::deleteDirectory($dir);
                }
            }
        }
    }

    public function down(): void
    {
        // Sin vuelta atrás a propósito: no se vuelven a publicar comprobantes.
    }
};
