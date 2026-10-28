<?php

namespace App\Http\Controllers;

class SeguimientoController extends Controller
{
    // slug => [nombre, icono]. El slug es el de la URL: /seguimiento/{slug}
    public const PLATAFORMAS = [
        'meta'          => ['nombre' => 'Meta',          'icono' => '🎯'],
        'global-educa'  => ['nombre' => 'Global Educa',  'icono' => '🌐'],
        'frances'       => ['nombre' => 'Francés',       'icono' => '🇫🇷'],
        'red-roo-reads' => ['nombre' => 'Red Roo Reads', 'icono' => '📖'],
    ];

    public function show(string $plataforma)
    {
        $info = self::PLATAFORMAS[$plataforma];

        return view('seguimiento.show', [
            'slug'   => $plataforma,
            'nombre' => $info['nombre'],
            'icono'  => $info['icono'],
        ]);
    }
}
