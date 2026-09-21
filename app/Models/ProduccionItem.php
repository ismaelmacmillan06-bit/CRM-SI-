<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProduccionItem extends Model
{
    protected $fillable = [
        'nivel', 'grado', 'colegio_id', 'colegio', 'tipo_pago',
        'serie', 'empresa', 'titulo', 'codigo_isbn', 'codigo_gs1', 'cantidad',
    ];
}
