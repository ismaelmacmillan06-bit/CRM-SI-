<?php

namespace App\Helpers;

class Niveles
{
    /**
     * Catálogo centralizado de niveles educativos para alumnos, con los
     * alias (incluyendo el formato real del CSV de altas masivas: "Bachillerato"
     * y "Universidad") que deben contarse como ese mismo nivel.
     */
    public static function map(): array
    {
        return [
            'Maternal'     => ['icono' => '🍼', 'color' => '#f59e0b', 'alias' => ['maternal']],
            'Preescolar'   => ['icono' => '🧸', 'color' => '#8b5cf6', 'alias' => ['preescolar', 'prescolar', 'kinder', 'kínder']],
            'Primaria'     => ['icono' => '✏️', 'color' => '#3b82f6', 'alias' => ['primaria']],
            'Secundaria'   => ['icono' => '📐', 'color' => '#10b981', 'alias' => ['secundaria', 'secu']],
            'Bachillerato' => ['icono' => '🎓', 'color' => '#E2231A', 'alias' => ['bachillerato', 'preparatoria', 'prepa', 'bach']],
            'Universidad'  => ['icono' => '🏛️', 'color' => '#0ea5e9', 'alias' => ['universidad', 'licenciatura', 'lic']],
        ];
    }

    public static function nombres(): array
    {
        return array_keys(static::map());
    }

    /**
     * Normaliza un valor de nivel para compararlo sin acentos ni mayúsculas
     * (ej. "Preparatoria " o "bachillerato" -> comparables entre sí).
     */
    public static function normalizar(?string $valor): string
    {
        $s = \Normalizer::normalize((string) $valor, \Normalizer::FORM_D);
        $s = preg_replace('/\p{Mn}/u', '', $s);
        $s = mb_strtolower(trim($s));
        return preg_replace('/[^a-z0-9]/', '', $s);
    }

    /**
     * Devuelve el nombre canónico del nivel (ej. "Bachillerato") a partir de
     * cualquier variante capturada (manual o carga masiva), o null si no
     * coincide con ningún alias conocido (va a "Otros").
     */
    public static function canonico(?string $valorCrudo): ?string
    {
        if ($valorCrudo === null || trim($valorCrudo) === '') {
            return null;
        }

        $norm = static::normalizar($valorCrudo);

        foreach (static::map() as $nombre => $datos) {
            foreach ($datos['alias'] as $alias) {
                if (static::normalizar($alias) === $norm) {
                    return $nombre;
                }
            }
        }

        return null;
    }
}
