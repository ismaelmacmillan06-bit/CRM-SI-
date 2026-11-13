<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TareaSI extends Model
{
    protected $table = 'tareas_si';
    protected $fillable = ['titulo', 'descripcion', 'audiencia', 'created_by'];

    /** A quién va dirigida la tarea */
    public const AUDIENCIAS = [
        'digital'   => 'Consultores digitales',
        'eca'       => 'Consultores ECA',
        'elt'       => 'Consultores ELT',
        'academico' => 'Consultores ECA y ELT',
    ];

    /** Rol del consultor en school_consultants que atiende cada audiencia */
    public const ROLES_COLEGIO = [
        'digital'   => ['digital'],
        'eca'       => ['eca'],
        'elt'       => ['elt'],
        'academico' => ['eca', 'elt'],
    ];

    public function colegios()
    {
        return $this->hasMany(TareaColegio::class, 'tarea_si_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function rolesColegio(): array
    {
        return self::ROLES_COLEGIO[$this->audiencia] ?? ['digital'];
    }

    public function esAcademica(): bool
    {
        return $this->audiencia !== 'digital';
    }

    /** Audiencias que el usuario puede publicar: admin todas, coordinador ECA/ELT */
    public static function audienciasQuePuedePublicar(?User $user): array
    {
        if (!$user) {
            return [];
        }
        if ($user->hasRole('admin')) {
            return array_keys(self::AUDIENCIAS);
        }
        if ($user->hasRole('coordinador')) {
            return ['eca', 'elt', 'academico'];
        }

        return [];
    }

    /** Editar / eliminar la tarea */
    public function puedeGestionar(?User $user): bool
    {
        return in_array($this->audiencia, self::audienciasQuePuedePublicar($user), true);
    }

    /**
     * Tareas que ve cada rol: admin, coordinador y ventas todas; digital las
     * suyas; ECA y ELT las dirigidas a su área.
     */
    public function scopeVisiblePara(Builder $q, User $user): Builder
    {
        if ($user->hasAnyRole(['admin', 'coordinador', 'representante_ventas'])) {
            return $q;
        }

        $audiencias = [];
        if ($user->hasRole('consultor_digital')) {
            $audiencias[] = 'digital';
        }
        if ($user->hasRole('consultor_eca')) {
            array_push($audiencias, 'eca', 'academico');
        }
        if ($user->hasRole('consultor_elt')) {
            array_push($audiencias, 'elt', 'academico');
        }

        return $q->whereIn('audiencia', array_unique($audiencias));
    }

    /**
     * IDs de colegio en los que el usuario puede marcar el estado de esta tarea
     * (null = todos los de la tarea).
     */
    public function colegiosMarcables(User $user): ?array
    {
        if ($user->hasRole('admin') || ($this->esAcademica() && $user->hasRole('coordinador'))) {
            return null;
        }

        $roles = [];
        if (!$this->esAcademica() && $user->hasRole('consultor_digital')) {
            $roles[] = 'digital';
        }
        foreach (['eca' => 'consultor_eca', 'elt' => 'consultor_elt'] as $rolColegio => $rolUsuario) {
            if (in_array($rolColegio, $this->rolesColegio(), true) && $user->hasRole($rolUsuario)) {
                $roles[] = $rolColegio;
            }
        }
        if (!$roles) {
            return [];
        }

        $consultant = Consultant::where('user_id', $user->id)->first();
        if (!$consultant) {
            return [];
        }

        return SchoolConsultant::where('consultant_id', $consultant->id)
            ->whereIn('role', $roles)
            ->pluck('school_id')
            ->unique()
            ->values()
            ->all();
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
