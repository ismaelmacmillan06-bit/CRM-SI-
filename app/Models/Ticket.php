<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = [
        'school_id', 'consultant_id', 'title',
        'description', 'status', 'priority', 'medium', 'productos', 'evidence', 'resolved_at',
    ];

    protected $casts = [
        'productos' => 'array',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function consultant()
    {
        return $this->belongsTo(Consultant::class);
    }
}