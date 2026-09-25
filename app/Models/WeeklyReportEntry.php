<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeeklyReportEntry extends Model
{
    protected $fillable = [
        'week_start', 'category', 'consultant_id', 'content', 'updated_by',
    ];

    protected $casts = ['week_start' => 'date'];

    public function consultant() { return $this->belongsTo(Consultant::class); }
    public function updatedBy()  { return $this->belongsTo(User::class, 'updated_by'); }
}
