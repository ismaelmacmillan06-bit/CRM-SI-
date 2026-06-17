<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TareaColegio extends Model
{
    protected $table = 'tarea_si_colegio';
    protected $fillable = ['tarea_si_id', 'school_id', 'status', 'updated_by'];

    public function tarea()
    {
        return $this->belongsTo(TareaSI::class, 'tarea_si_id');
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
