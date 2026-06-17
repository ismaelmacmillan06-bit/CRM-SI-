<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TareaSI extends Model
{
    protected $table = 'tareas_si';
    protected $fillable = ['titulo', 'descripcion', 'created_by'];

    public function colegios()
    {
        return $this->hasMany(TareaColegio::class, 'tarea_si_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function progreso(): array
    {
        $counts = $this->colegios()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'pendiente'  => $counts['pendiente']  ?? 0,
            'en_proceso' => $counts['en_proceso']  ?? 0,
            'realizada'  => $counts['realizada']   ?? 0,
            'total'      => $counts->sum(),
        ];
    }
}
