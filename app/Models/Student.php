<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = [
        'school_id', 'name', 'last_name',
        'mee_username', 'mee_password',
        'grade', 'level'
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}