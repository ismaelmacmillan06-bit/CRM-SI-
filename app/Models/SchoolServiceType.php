<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolServiceType extends Model
{
    protected $fillable = ['name', 'icon', 'color', 'order', 'active'];

    protected $casts = ['active' => 'boolean'];

    public function schools()
    {
        return $this->belongsToMany(School::class, 'school_service', 'school_service_type_id', 'school_id')
                    ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('active', true)->orderBy('order');
    }
}
