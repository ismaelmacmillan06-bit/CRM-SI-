<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolFile extends Model
{
    protected $fillable = ['school_id', 'uploaded_by', 'nombre', 'ruta', 'mime', 'tamano'];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
