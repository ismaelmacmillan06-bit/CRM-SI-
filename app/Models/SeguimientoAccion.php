<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeguimientoAccion extends Model
{
    protected $table = 'seguimiento_acciones';

    protected $fillable = ['school_id', 'plataforma', 'level_id', 'accion', 'status', 'completed_at', 'completed_by', 'notes'];

    protected $casts = ['completed_at' => 'datetime'];

    public const STATUS = [
        'pending'     => '⏳ Pendiente',
        'in_progress' => '🔄 En proceso',
        'done'        => '✅ Completado',
    ];

    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
