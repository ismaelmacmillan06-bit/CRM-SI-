<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    protected $fillable = [
        'school_id', 'name', 'last_name', 'email',
        'grade', 'subject', 'mee_username', 'mee_password',
    ];

    const ROLES = [
        'docente'          => 'Docente',
        'director_general' => 'Director General',
        'director_nivel'   => 'Director de Nivel',
        'coordinador'      => 'Coordinador',
        'dueno'            => 'Dueño',
        'admin_mee'        => 'Administrador MEE',
        'coord_ingles'     => 'Coordinador Inglés',
        'coord_espanol'    => 'Coordinador Español',
    ];

    const ROLE_BADGES = [
        'docente'          => 'badge-gray',
        'director_general' => 'badge-info',
        'director_nivel'   => 'badge-warning',
        'coordinador'      => 'badge-success',
        'dueno'            => 'badge-danger',
        'admin_mee'        => 'badge-warning',
        'coord_ingles'     => 'badge-success',
        'coord_espanol'    => 'badge-info',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function roles()
    {
        return $this->hasMany(TeacherRole::class);
    }

    public function books()
    {
        return $this->hasMany(TeacherBook::class);
    }
}
