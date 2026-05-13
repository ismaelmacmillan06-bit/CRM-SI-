<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Consultant extends Model
{
    protected $fillable = ['user_id', 'phone', 'zone', 'photo'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function schools()
    {
        return $this->hasMany(School::class);
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