<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    protected $fillable = [
        'school_id', 'consultant_id', 'visit_date',
        'scheduled_date', 'status', 'notes', 
        'summary', 'evidence', 'next_visit_date'
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function consultant()
    {
        return $this->belongsTo(Consultant::class);
    }
}