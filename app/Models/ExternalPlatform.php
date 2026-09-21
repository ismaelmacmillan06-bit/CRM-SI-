<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalPlatform extends Model
{
    protected $fillable = ['name', 'slug', 'icon', 'order'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function steps()
    {
        return $this->hasMany(ExternalPlatformStep::class)->orderBy('order');
    }
}
