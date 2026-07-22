<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\SchoolConsultant;

class Consultant extends Model
{
    protected $fillable = ['user_id', 'phone', 'zone', 'photo'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function schools()
    {
        return $this->belongsToMany(School::class, 'school_consultants')
                    ->withPivot('role')
                    ->withTimestamps()
                    ->distinct();
    }

    public function schoolConsultants()
    {
        return $this->hasMany(SchoolConsultant::class);
    }

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}