<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeguimientoRecursoAudiencia extends Model
{
    protected $table = 'seguimiento_recurso_audiencias';

    protected $fillable = [
        'seguimiento_recurso_id',
        'rol',
    ];

    public function recurso()
    {
        return $this->belongsTo(SeguimientoRecurso::class, 'seguimiento_recurso_id');
    }
}
