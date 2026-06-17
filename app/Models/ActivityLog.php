<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = ['user_id', 'school_id', 'tipo', 'icono', 'descripcion'];

    public static function log(string $tipo, string $descripcion, ?int $schoolId = null, string $icono = '📝'): void
    {
        static::create([
            'user_id'     => auth()->id(),
            'school_id'   => $schoolId,
            'tipo'        => $tipo,
            'icono'       => $icono,
            'descripcion' => $descripcion,
        ]);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
