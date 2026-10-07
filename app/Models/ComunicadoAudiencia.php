<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComunicadoAudiencia extends Model
{
    protected $table = 'comunicado_audiencias';
    protected $fillable = ['comunicado_id', 'rol'];
}
