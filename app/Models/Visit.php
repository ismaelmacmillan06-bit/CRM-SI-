<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    protected $fillable = [
        'school_id', 'consultant_id', 'visit_date',
        'scheduled_date', 'status', 'notes',
        'summary', 'evidence', 'next_visit_date', 'motivo',
        'modalidad', 'productos', 'tipo', 'prioridad',
    ];

    protected $casts = [
        'productos' => 'array',
    ];

    public const PRODUCTOS = ['MEE', 'Ree Roo Reads', 'Lego', 'Equipamiento', 'Global Educa'];

    // Icono y color por producto, para tablas y dashboard
    public const PRODUCTO_ESTILOS = [
        'MEE'           => ['📘', '#0ea5e9'],
        'Ree Roo Reads' => ['🦘', '#f59e0b'],
        'Lego'          => ['🧱', '#dc2626'],
        'Equipamiento'  => ['🖥️', '#64748b'],
        'Global Educa'  => ['🌎', '#16a34a'],
    ];

    public const MODALIDADES = ['presencial' => '🏫 Presencial', 'virtual' => '💻 Virtual'];

    public const TIPOS = ['programada' => '📅 Programada', 'emergencia' => '🚨 Emergencia'];

    public const PRIORIDADES = [
        'baja'        => 'Baja',
        'media'       => 'Media',
        'alta'        => 'Alta',
        'muy_urgente' => 'Muy urgente',
    ];

    public const PRIORIDAD_COLORES = [
        'baja'        => ['#dcfce7', '#16a34a'],
        'media'       => ['#e0f2fe', '#0369a1'],
        'alta'        => ['#fef3c7', '#b45309'],
        'muy_urgente' => ['#fee2e2', '#dc2626'],
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function consultant()
    {
        return $this->belongsTo(Consultant::class);
    }

    // Personal de Equipo SI que acudió a la visita (puede ser más de uno)
    public function attendees()
    {
        return $this->belongsToMany(Consultant::class, 'visit_attendees')->withTimestamps();
    }
}