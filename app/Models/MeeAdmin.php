<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeeAdmin extends Model
{
    protected $fillable = ['school_id', 'username', 'password_plain'];

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}