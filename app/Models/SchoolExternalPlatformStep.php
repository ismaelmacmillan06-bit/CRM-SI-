<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolExternalPlatformStep extends Model
{
    protected $table = 'school_external_platform_step';

    protected $fillable = [
        'school_id', 'external_platform_step_id', 'status',
        'completed_at', 'completed_by', 'notes',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function step()
    {
        return $this->belongsTo(ExternalPlatformStep::class, 'external_platform_step_id');
    }

    public function completedBy()
    {
        return $this->belongsTo(Consultant::class, 'completed_by');
    }
}
