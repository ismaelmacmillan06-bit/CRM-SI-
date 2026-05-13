<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolConsultant extends Model
{
    protected $fillable = ['school_id', 'consultant_id', 'role'];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function consultant()
    {
        return $this->belongsTo(Consultant::class);
    }
}