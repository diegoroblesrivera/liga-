<?php

namespace App\Models;

use App\Models\Concerns\FiltradoPorLigaVia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

// QR de la lista de una fecha (torneo + número de fecha). El token va en la URL del QR.
class ListaFecha extends Model
{
    use FiltradoPorLigaVia;

    protected $table = 'listas_fecha';

    protected static array $ligaVia = ['torneo_id', Torneo::class];

    protected $fillable = ['torneo_id', 'numero_fecha', 'token'];

    protected $casts = ['numero_fecha' => 'integer'];

    public function torneo(): BelongsTo
    {
        return $this->belongsTo(Torneo::class);
    }

    public static function deFecha(Torneo $torneo, int $numero): self
    {
        return static::firstOrCreate(
            ['torneo_id' => $torneo->id, 'numero_fecha' => $numero],
            ['token' => static::nuevoToken()]
        );
    }

    public static function nuevoToken(): string
    {
        return Str::random(40);
    }

    public function url(): string
    {
        return route('lista.escanear', $this->token);
    }
}
