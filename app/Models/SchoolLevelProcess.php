<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolLevelProcess extends Model
{
    protected $table = 'school_level_process';
    protected $fillable = [
        'school_level_id', 'process_id', 'status',
        'completed_at', 'completed_by', 'notes'
    ];

    public function schoolLevel()
    {
        return $this->belongsTo(SchoolLevel::class);
    }

    public function process()
    {
        return $this->belongsTo(Process::class);
    }

    public function completedBy()
    {
        return $this->belongsTo(Consultant::class, 'completed_by');
    }
}