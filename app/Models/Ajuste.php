<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Ajustes globales editables por el super admin (clave → valor).
class Ajuste extends Model
{
    protected $table = 'ajustes';
    protected $primaryKey = 'clave';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['clave', 'valor'];

    public static function valor(string $clave, mixed $porDefecto = null): mixed
    {
        return static::whereKey($clave)->value('valor') ?? $porDefecto;
    }

    public static function fijar(string $clave, string $valor): void
    {
        static::updateOrCreate(['clave' => $clave], ['valor' => $valor]);
    }

    public static function precioProAnual(): int
    {
        return (int) static::valor('precio_pro_anual', 29990);
    }
}
