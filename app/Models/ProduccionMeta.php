<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProduccionMeta extends Model
{
    protected $table = 'produccion_meta';

    protected $fillable = [
        'archivo_nombre', 'corte', 'total_filas', 'cargado_por_id', 'cargado_en',
    ];

    protected $casts = [
        'cargado_en' => 'datetime',
    ];

    public function cargadoPor()
    {
        return $this->belongsTo(User::class, 'cargado_por_id');
    }
}
