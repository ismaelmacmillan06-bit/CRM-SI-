<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeguimientoMeta extends Model
{
    protected $table = 'seguimiento_meta';

    protected $fillable = [
        'school_id', 'administrador', 'usuario', 'contrasena', 'capacitacion',
        'fecha_capacitacion', 'fecha_aplicacion', 'notas', 'updated_by',
    ];

    protected $casts = [
        'fecha_capacitacion' => 'date',
        'fecha_aplicacion'   => 'date',
    ];

    // Campos que se llenan desde la tabla de Meta (sin school_id ni updated_by)
    public const CAMPOS = [
        'administrador'      => 'Administrador',
        'usuario'            => 'Usuario',
        'contrasena'         => 'Contraseña',
        'capacitacion'       => 'Capacitación',
        'fecha_capacitacion' => 'Fecha de capacitación',
        'fecha_aplicacion'   => 'Fecha de aplicación',
        'notas'              => 'Notas',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Le falta alguna de las dos fechas
    public function fechasPendientes(): bool
    {
        return !$this->fecha_capacitacion || !$this->fecha_aplicacion;
    }
}
