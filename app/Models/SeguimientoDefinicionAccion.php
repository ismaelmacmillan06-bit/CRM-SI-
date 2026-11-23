<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeguimientoDefinicionAccion extends Model
{
    protected $table = 'seguimiento_definicion_acciones';

    protected $fillable = [
        'plataforma',
        'clave',
        'etiqueta',
        'orden',
    ];

    protected $casts = [
        'orden' => 'integer',
    ];
}
