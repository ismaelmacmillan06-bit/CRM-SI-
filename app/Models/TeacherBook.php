<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherBook extends Model
{
    protected $fillable = ['teacher_id', 'adoption_id'];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function adoption()
    {
        return $this->belongsTo(Adoption::class);
    }
}