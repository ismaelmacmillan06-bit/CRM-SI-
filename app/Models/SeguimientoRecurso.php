<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SeguimientoRecurso extends Model
{
    public const AUDIENCIAS = [
        'consultor_digital'    => 'Consultores digitales',
        'coordinador'          => 'Coordinadores',
        'consultor_eca'        => 'Consultores ECA',
        'consultor_elt'        => 'Consultores ELT',
        'representante_ventas' => 'Representantes de ventas',
        'todos'                => 'Todos (general)',
    ];

    public const EXTENSIONES_PERMITIDAS = [
        'pdf', 'doc', 'docx', 'odt',
        'xls', 'xlsx', 'csv', 'ods',
        'ppt', 'pptx', 'odp',
        'txt', 'jpg', 'jpeg', 'png', 'webp', 'gif',
        'zip', 'rar', '7z',
    ];

    protected $table = 'seguimiento_recursos';

    protected $fillable = [
        'plataforma',
        'categoria',
        'titulo',
        'descripcion',
        'archivo',
        'archivo_nombre',
        'extension',
        'mime_type',
        'tamano',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function audiencias()
    {
        return $this->hasMany(SeguimientoRecursoAudiencia::class, 'seguimiento_recurso_id');
    }

    public function audienciaRoles(): array
    {
        return $this->audiencias->pluck('rol')->all();
    }

    public function scopeVisiblePara(Builder $q, User $usuario): Builder
    {
        if ($usuario->hasRole('admin')) {
            return $q;
        }

        $roles = array_merge($usuario->getRoleNames()->all(), ['todos']);

        return $q->whereHas('audiencias', fn (Builder $a) => $a->whereIn('rol', $roles));
    }

    public function esVisiblePara(User $usuario): bool
    {
        if ($usuario->hasRole('admin')) {
            return true;
        }

        $roles = array_merge($usuario->getRoleNames()->all(), ['todos']);

        return $this->audiencias()->whereIn('rol', $roles)->exists();
    }

    public static function puedeGestionar(User $usuario): bool
    {
        return $usuario->hasRole('admin');
    }

    public function formatoInfo(): array
    {
        $ext = strtolower($this->extension ?? '');

        return match (true) {
            $ext === 'pdf' => [
                'tipo' => 'pdf',
                'etiqueta' => 'PDF',
                'color' => '#dc2626',
                'bg' => '#fef2f2',
                'border' => '#fecaca',
                'previsualizable' => true,
            ],
            in_array($ext, ['xls', 'xlsx', 'csv', 'ods'], true) => [
                'tipo' => 'excel',
                'etiqueta' => strtoupper($ext),
                'color' => '#15803d',
                'bg' => '#f0fdf4',
                'border' => '#bbf7d0',
                'previsualizable' => in_array($ext, ['xls', 'xlsx', 'csv'], true),
            ],
            in_array($ext, ['doc', 'docx', 'odt'], true) => [
                'tipo' => 'word',
                'etiqueta' => strtoupper($ext),
                'color' => '#1d4ed8',
                'bg' => '#eff6ff',
                'border' => '#bfdbfe',
                'previsualizable' => false,
            ],
            in_array($ext, ['ppt', 'pptx', 'odp'], true) => [
                'tipo' => 'ppt',
                'etiqueta' => strtoupper($ext),
                'color' => '#c2410c',
                'bg' => '#fff7ed',
                'border' => '#fed7aa',
                'previsualizable' => false,
            ],
            in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true) => [
                'tipo' => 'image',
                'etiqueta' => strtoupper($ext),
                'color' => '#7c3aed',
                'bg' => '#f5f3ff',
                'border' => '#ddd6fe',
                'previsualizable' => true,
            ],
            $ext === 'txt' => [
                'tipo' => 'text',
                'etiqueta' => 'TXT',
                'color' => '#475569',
                'bg' => '#f8fafc',
                'border' => '#cbd5e1',
                'previsualizable' => true,
            ],
            in_array($ext, ['zip', 'rar', '7z'], true) => [
                'tipo' => 'archive',
                'etiqueta' => strtoupper($ext),
                'color' => '#a16207',
                'bg' => '#fefce8',
                'border' => '#fde047',
                'previsualizable' => false,
            ],
            default => [
                'tipo' => 'file',
                'etiqueta' => strtoupper($ext ?: 'ARCHIVO'),
                'color' => '#475569',
                'bg' => '#f8fafc',
                'border' => '#e2e8f0',
                'previsualizable' => false,
            ],
        };
    }

    public function tamanoFormateado(): string
    {
        $bytes = (int) $this->tamano;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 0) . ' KB';
        }
        return $bytes . ' B';
    }
}
