<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherRole extends Model
{
    protected $fillable = ['teacher_id', 'role'];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
}
