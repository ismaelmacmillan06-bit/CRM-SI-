<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bundle extends Model
{
    protected $fillable = ['serie', 'name', 'grade', 'level', 'role', 'type'];

    public function schools()
    {
        return $this->belongsToMany(School::class, 'school_bundle')
                    ->withPivot('quantity', 'acquired_at')
                    ->withTimestamps();
    }
}