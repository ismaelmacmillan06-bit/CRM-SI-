<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    protected $fillable = [
        'name', 'nexus_id', 'address', 'city',
        'phone', 'email', 'status', 'notes', 'consultant_id'
    ];

    public function consultant()
    {
        return $this->belongsTo(Consultant::class);
    }

    public function levels()
{
    return $this->belongsToMany(Level::class, 'school_level', 'school_id', 'level_id');
}

    public function schoolLevels()
    {
        return $this->hasMany(SchoolLevel::class);
    }

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function teachers()
    {
        return $this->hasMany(Teacher::class);
    }

    public function adoptions()
    {
        return $this->belongsToMany(Adoption::class, 'school_adoption')
                    ->withPivot('quantity', 'acquired_at', 'level_id')
                    ->withTimestamps();
    }
    public function meeAdmins()
{
    return $this->hasMany(MeeAdmin::class);
}

public function students()
{
    return $this->hasMany(Student::class);
}

public function bundles()
{
    return $this->belongsToMany(Bundle::class, 'school_bundle')
                ->withPivot('quantity', 'acquired_at')
                ->withTimestamps();
}


public function schoolConsultants()
{
    return $this->hasMany(SchoolConsultant::class);
}

public function consultorDigital()
{
    return $this->hasOneThrough(Consultant::class, SchoolConsultant::class, 'school_id', 'id', 'id', 'consultant_id')
                ->where('school_consultants.role', 'digital');
}

public function consultorEca()
{
    return $this->hasOneThrough(Consultant::class, SchoolConsultant::class, 'school_id', 'id', 'id', 'consultant_id')
                ->where('school_consultants.role', 'eca');
}

public function consultorElt()
{
    return $this->hasOneThrough(Consultant::class, SchoolConsultant::class, 'school_id', 'id', 'id', 'consultant_id')
                ->where('school_consultants.role', 'elt');
}

public function representanteVentas()
{
    return $this->hasOneThrough(Consultant::class, SchoolConsultant::class, 'school_id', 'id', 'id', 'consultant_id')
                ->where('school_consultants.role', 'ventas');
}
}