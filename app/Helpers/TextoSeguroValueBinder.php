<?php

namespace App\Helpers;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

/**
 * Ningún Excel del sistema escribe fórmulas a propósito. Si un texto capturado
 * por el usuario empieza con "=" (título de ticket, nota, nombre...), PhpSpreadsheet
 * lo trataba como fórmula: el archivo completo fallaba al generarse, o Excel la
 * ejecutaba al abrirlo. Aquí se guarda siempre como texto.
 */
class TextoSeguroValueBinder extends DefaultValueBinder
{
    public function bindValue(Cell $cell, mixed $value): bool
    {
        if (is_string($value) && str_starts_with(ltrim($value), '=')) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }
}
