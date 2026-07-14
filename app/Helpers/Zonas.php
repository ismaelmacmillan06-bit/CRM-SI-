<?php

namespace App\Helpers;

class Zonas
{
    public static function map(): array
    {
        return [
            'Pacífico' => ['baja california sur', 'baja california', 'sonora', 'hermosillo', 'sinaloa', 'culiacan', 'nayarit', 'jalisco', 'guadalajara', 'colima'],
            'Norte'    => ['chihuahua', 'coahuila', 'nuevo leon', 'tamaulipas', 'durango'],
            'Centro'   => ['edomex', 'estado de mexico', 'ciudad de mexico', 'cdmx', 'hidalgo', 'tlaxcala', 'morelos', 'guerrero'],
            'Bajío'    => ['guanajuato', 'queretaro', 'aguascalientes', 'san luis potosi', 'michoacan', 'zacatecas'],
            'Sureste'  => ['veracruz', 'oaxaca', 'puebla', 'chiapas', 'tabasco', 'campeche', 'yucatan', 'quintana roo'],
        ];
    }

    public static function normalize(string $s): string
    {
        $s = mb_strtolower(trim($s));
        $s = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ü', 'Ñ'],
            ['a', 'e', 'i', 'o', 'u', 'u', 'n', 'a', 'e', 'i', 'o', 'u', 'u', 'n'],
            $s
        );
        return preg_replace('/\s+/', ' ', $s);
    }

    public static function detectZona(string $city): string
    {
        $normalized = static::normalize($city);
        if ($normalized === '') return 'Sin zona';

        foreach (static::map() as $zona => $estados) {
            foreach ($estados as $estado) {
                if (str_contains($normalized, $estado)) {
                    return $zona;
                }
            }
        }

        return 'Sin zona';
    }

    public static function colors(): array
    {
        return [
            'Pacífico' => ['hex' => '#10b981', 'excel' => '10B981'],
            'Norte'    => ['hex' => '#3b82f6', 'excel' => '3B82F6'],
            'Centro'   => ['hex' => '#8b5cf6', 'excel' => '8B5CF6'],
            'Bajío'    => ['hex' => '#f97316', 'excel' => 'F97316'],
            'Sureste'  => ['hex' => '#e94560', 'excel' => 'E94560'],
            'Sin zona' => ['hex' => '#6b7280', 'excel' => '6B7280'],
        ];
    }

    public static function estados(): array
    {
        return [
            'Pacífico' => 'BC · BCS · Sonora · Sinaloa · Nayarit · Jalisco · Colima',
            'Norte'    => 'Chihuahua · Coahuila · Nuevo León · Tamaulipas · Durango',
            'Centro'   => 'Hidalgo · Tlaxcala · Edo. México · CDMX · Morelos · Guerrero',
            'Bajío'    => 'Guanajuato · Querétaro · Ags. · S.L.P. · Michoacán · Zacatecas',
            'Sureste'  => 'Veracruz · Oaxaca · Puebla · Chiapas · Tabasco · Campeche · Yucatán · Q.Roo',
        ];
    }
}
