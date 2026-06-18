<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BundleResurtido extends Model
{
    protected $fillable = [
        'school_id', 'bundle_id', 'cantidad_anterior', 'cantidad_resurtido',
        'cantidad_nueva', 'autorizado_por', 'fecha', 'user_id',
    ];

    protected $casts = ['fecha' => 'date'];

    public function school() { return $this->belongsTo(School::class); }
    public function bundle() { return $this->belongsTo(Bundle::class); }
    public function user()   { return $this->belongsTo(User::class); }
}
