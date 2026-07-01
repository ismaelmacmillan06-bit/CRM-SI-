<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $titulo
 * @property string|null $descripcion
 * @property string|null $archivo
 * @property string|null $archivo_nombre
 * @property string|null $archivo_tipo
 * @property int $user_id
 */
class ArchivoSI extends Model
{
    protected $table = 'archivos_si';

    protected $fillable = ['titulo', 'descripcion', 'archivo', 'archivo_nombre', 'archivo_tipo'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
