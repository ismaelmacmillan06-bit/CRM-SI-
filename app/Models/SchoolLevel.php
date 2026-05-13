<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolLevel extends Model
{
    protected $table = 'school_level'; 
    
    protected $fillable = ['school_id', 'level_id'];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    public function processes()
    {
        return $this->hasMany(SchoolLevelProcess::class);
    }
}