<?php

namespace App\Http\Controllers;

use App\Models\Ajuste;
use App\Models\Liga;
use App\Services\ActivarPro;
use Illuminate\Http\Request;

// Panel del super admin: todos los campeonatos, su plan y el precio del Pro.
class AdminCampeonatosController extends Controller
{
    public function index()
    {
        $campeonatos = Liga::with('owner')
            ->withCount('equipos')
            ->orderByDesc('pro_hasta')
            ->orderBy('nombre')
            ->get();

        return view('admin.campeonatos', [
            'campeonatos' => $campeonatos,
            'precio' => Ajuste::precioProAnual(),
            'ingresos' => \App\Models\PagoPro::where('estado', 'aprobado')->where('medio', 'mercadopago')->sum('monto'),
        ]);
    }

    // Activa o extiende el Pro un año (pago por transferencia, cortesía, etc.).
    public function activarPro(Request $request, Liga $liga, ActivarPro $activar)
    {
        $data = $request->validate([
            'monto' => ['required', 'integer', 'min:0'],
            'nota' => ['nullable', 'string', 'max:255'],
        ]);

        $pago = $activar->manual($liga, $request->user(), $data['monto'], $data['nota'] ?? null);

        return back()->with('status', "\"{$liga->nombre}\" es Pro hasta el {$pago->pro_hasta->format('d/m/Y')}.");
    }

    public function quitarPro(Liga $liga)
    {
        $liga->forceFill(['pro_hasta' => null])->save();

        return back()->with('status', "\"{$liga->nombre}\" volvió al plan Gratis.");
    }

    public function precio(Request $request)
    {
        $data = $request->validate(['precio' => ['required', 'integer', 'min:1000', 'max:10000000']]);

        Ajuste::fijar('precio_pro_anual', (string) $data['precio']);

        return back()->with('status', 'Precio del Pro anual actualizado a $'.number_format($data['precio'], 0, ',', '.').'.');
    }
}
