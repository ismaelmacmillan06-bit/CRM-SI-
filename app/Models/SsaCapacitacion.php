<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SsaCapacitacion extends Model
{
    protected $table = 'ssa_capacitaciones';

    protected $fillable = [
        'school_id', 'user_id', 'fecha', 'hora', 'tipo', 'estatus', 'notas',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
