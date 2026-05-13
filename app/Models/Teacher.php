<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    protected $fillable = [
    'school_id', 'name', 'last_name', 'email', 
    'grade', 'role', 'subject', 'mee_username', 'mee_password'
];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function books()
    {
        return $this->hasMany(TeacherBook::class);
    }
}