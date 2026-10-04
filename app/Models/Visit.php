<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    protected $fillable = [
        'school_id', 'consultant_id', 'visit_date',
        'scheduled_date', 'status', 'notes',
        'summary', 'evidence', 'next_visit_date', 'motivo'
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function consultant()
    {
        return $this->belongsTo(Consultant::class);
    }

    // Personal de Equipo SI que acudió a la visita (puede ser más de uno)
    public function attendees()
    {
        return $this->belongsToMany(Consultant::class, 'visit_attendees')->withTimestamps();
    }
}