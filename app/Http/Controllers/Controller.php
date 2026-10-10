<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;

abstract class Controller
{
    // Habilita $this->authorize() (Policies) y $this->validate() en todos los controladores.
    use AuthorizesRequests, ValidatesRequests;
}
