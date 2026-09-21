<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalPlatformStep extends Model
{
    protected $fillable = ['external_platform_id', 'name', 'slug', 'order'];

    public function platform()
    {
        return $this->belongsTo(ExternalPlatform::class, 'external_platform_id');
    }
}
