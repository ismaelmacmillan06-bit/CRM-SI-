<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Adoption extends Model
{
    protected $fillable = ['name', 'level', 'subject'];

    public function schools()
    {
        return $this->belongsToMany(School::class, 'school_adoption')
                    ->withPivot('quantity', 'acquired_at', 'level_id')
                    ->withTimestamps();
    }

    public function teacherBooks()
    {
        return $this->hasMany(TeacherBook::class);
    }
}