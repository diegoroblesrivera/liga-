<?php

namespace Tests;

use App\Support\LigaActual;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // El campeonato actual vive en una propiedad estática: cada test parte sin ninguno.
        LigaActual::fijar(null);
    }
}
