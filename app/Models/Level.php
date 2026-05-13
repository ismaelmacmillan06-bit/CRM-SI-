<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Level extends Model
{
    protected $fillable = ['name'];

    public function schools()
{
    return $this->belongsToMany(School::class, 'school_level', 'level_id', 'school_id');
}

    public function schoolLevels()
    {
        return $this->hasMany(SchoolLevel::class);
    }
}