<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Comunicado extends Model
{
    public const AUDIENCIAS = [
        'consultor_digital'   => 'Consultores digitales',
        'coordinador'         => 'Coordinadores',
        'consultor_eca'       => 'Consultores ECA',
        'consultor_elt'       => 'Consultores ELT',
        'representante_ventas' => 'Representantes de ventas',
        'todos'               => 'Todos (general)',
    ];

    protected $fillable = [
        'titulo', 'descripcion', 'archivo', 'archivo_nombre',
        'archivo_tipo', 'enlace', 'enlace_texto', 'fecha_termino', 'user_id',
    ];

    protected $casts = ['fecha_termino' => 'date'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function audiencias()
    {
        return $this->hasMany(ComunicadoAudiencia::class);
    }

    public function audienciaRoles(): array
    {
        return $this->audiencias->pluck('rol')->all();
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

    // Admin ve todo; los demás ven lo dirigido a su rol o a todos
    public function scopeVisiblePara(Builder $q, User $usuario): Builder
    {
        if ($usuario->hasRole('admin')) {
            return $q;
        }

        $roles = array_merge($usuario->getRoleNames()->all(), ['todos']);

        return $q->whereHas('audiencias', fn($a) => $a->whereIn('rol', $roles));
    }

    public static function puedePublicar(User $usuario): bool
    {
        return $usuario->hasRole('admin');
    }
}
