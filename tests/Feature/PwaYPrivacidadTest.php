<?php

namespace Tests\Feature;

use Tests\TestCase;

class PwaYPrivacidadTest extends TestCase
{
    public function test_la_politica_de_privacidad_es_publica_y_permite_pedir_el_borrado(): void
    {
        $this->get('/privacidad')
            ->assertOk()
            ->assertSee('Política de privacidad')
            ->assertSee('Puedes borrar tus datos cuando quieras.')
            ->assertSee(config('app.correo_privacidad'))
            ->assertSee('Diego Robles Rivera')
            ->assertSee('DT de su equipo')
            // Sin campos por completar del borrador
            ->assertDontSee('[nombre')
            ->assertDontSee('[correo')
            ->assertDontSee('[edad')
            ->assertDontSee('[12 meses]')
            ->assertDontSee('[30 días]')
            ->assertDontSee('[verificar');
    }

    public function test_el_pie_y_el_registro_enlazan_la_politica(): void
    {
        $this->get('/')->assertOk()->assertSee(route('privacidad'));
        $this->get('/register')->assertOk()->assertSee('aceptas la')->assertSee(route('privacidad'));
    }

    public function test_las_paginas_declaran_la_app_instalable(): void
    {
        foreach (['/', '/login', '/privacidad'] as $url) {
            $this->get($url)
                ->assertSee('<link rel="manifest" href="/manifest.webmanifest">', false)
                ->assertSee('apple-touch-icon', false);
        }

        $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true);
        $this->assertSame('TuTorneo', $manifest['short_name']);
        $this->assertSame('standalone', $manifest['display']);
        foreach ($manifest['icons'] as $icono) {
            $this->assertFileExists(public_path(ltrim($icono['src'], '/')));
        }
        $this->assertFileExists(public_path('sw.js'));
        $this->assertFileExists(public_path('offline.html'));
    }
}
