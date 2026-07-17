<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Comunicado extends Model
{
    protected $fillable = [
        'titulo', 'descripcion', 'archivo', 'archivo_nombre',
        'archivo_tipo', 'enlace', 'enlace_texto', 'fecha_termino', 'user_id',
    ];

    protected $casts = ['fecha_termino' => 'date'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActivos(Builder $q): Builder
    {
        return $q->where(fn($q) =>
            $q->whereNull('fecha_termino')->orWhere('fecha_termino', '>=', today())
        );
    }

    public function scopePasados(Builder $q): Builder
    {
        return $q->whereNotNull('fecha_termino')->where('fecha_termino', '<', today());
    }
}
