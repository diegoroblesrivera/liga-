<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Acompanante extends Model
{
    protected $table = 'acompanantes';

    protected $fillable = ['user_id', 'nombre', 'run'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
