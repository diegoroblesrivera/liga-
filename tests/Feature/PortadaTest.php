<?php

namespace Tests\Feature;

use App\Models\Ajuste;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Requiere MySQL (las migraciones usan vistas, triggers y ALTER ... ENUM).
class PortadaTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_portada_muestra_todas_las_secciones_en_orden(): void
    {
        $this->get(route('home'))->assertOk()
            ->assertSeeInOrder(['Tu fútbol, organizado', 'Pichangas semanales', 'Todo lo que necesita tu liga', 'Tu campeonato listo en 3 pasos', 'Cada gol cuenta', 'Empieza gratis', 'Preguntas frecuentes'])
            ->assertSee('id="funciones"', false)->assertSee('id="como-funciona"', false)->assertSee('id="planes"', false)->assertSee('id="campeonatos"', false)
            ->assertSee('Lista con QR')->assertSee('Carga por Excel')->assertSee('cuota fija', false)
            ->assertSee(route('precios'), false);
    }

    public function test_la_pagina_de_precios_usa_el_precio_vigente(): void
    {
        Ajuste::fijar('precio_pro_anual', '34990');

        $this->get(route('precios'))->assertOk()
            ->assertSee('$34.990')->assertSee('Hasta 8')->assertSee('Equipos ilimitados')
            ->assertSee('Pichichi')->assertSee('¿Qué pasa cuando vence el año?');
        $this->get(route('home'))->assertSee('$34.990');
    }
}
