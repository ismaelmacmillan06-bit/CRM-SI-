<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Process extends Model
{
    protected $fillable = ['name', 'slug', 'order'];

    public function schoolLevelProcesses()
    {
        return $this->hasMany(SchoolLevelProcess::class);
    }
}