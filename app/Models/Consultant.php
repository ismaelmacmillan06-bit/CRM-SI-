<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\SchoolConsultant;

class Consultant extends Model
{
    protected $fillable = ['user_id', 'phone', 'zone', 'photo', 'responsable_seguimiento'];

    protected $casts = [
        'responsable_seguimiento' => 'array',
    ];

    /**
     * Plataformas de Seguimiento de las que es responsable: las marcadas en Equipo SI
     * (consultor digital) o las automáticas de su rol (consul_ged → Global Educa).
     */
    public function plataformasResponsable(): array
    {
        if ($this->user?->hasRole('consul_ged')) {
            return \App\Http\Controllers\SeguimientoController::PLATAFORMAS_CONSUL_GED;
        }

        return (array) ($this->responsable_seguimiento ?? []);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function schools()
    {
        return $this->belongsToMany(School::class, 'school_consultants')
                    ->withPivot('role')
                    ->withTimestamps()
                    ->distinct();
    }

    public function schoolConsultants()
    {
        return $this->hasMany(SchoolConsultant::class);
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