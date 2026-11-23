<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserNotification extends Model
{
    protected $fillable = [
        'user_id',
        'actor_id',
        'school_id',
        'category',
        'event_type',
        'priority',
        'icon',
        'title',
        'message',
        'details',
        'action_url',
        'action_label',
        'read_at',
    ];

    protected $casts = [
        'details' => 'array',
        'read_at' => 'datetime',
    ];

    public const CATEGORIAS = [
        'recordatorios' => [
            'label' => 'Recordatorios y Capacitaciones',
            'icon'  => '⏰',
            'color' => '#dc2626',
            'bg'    => '#fef2f2',
        ],
        'reporte_semanal' => [
            'label' => 'Reporte Semanal',
            'icon'  => '🗓️',
            'color' => '#7c3aed',
            'bg'    => '#f5f3ff',
        ],
        'tareas' => [
            'label' => 'Tareas SI',
            'icon'  => '✅',
            'color' => '#059669',
            'bg'    => '#ecfdf5',
        ],
        'tablero' => [
            'label' => 'Tablero SI',
            'icon'  => '📌',
            'color' => '#d97706',
            'bg'    => '#fffbeb',
        ],
        'manuales' => [
            'label' => 'Manuales y Formatos',
            'icon'  => '📚',
            'color' => '#2563eb',
            'bg'    => '#eff6ff',
        ],
        'herramientas' => [
            'label' => 'Herramientas SI',
            'icon'  => '🛠️',
            'color' => '#475569',
            'bg'    => '#f8fafc',
        ],
    ];

    public const PRIORIDADES = [
        'low'    => ['label' => 'Baja',    'color' => '#475569', 'bg' => '#f1f5f9'],
        'normal' => ['label' => 'Normal',  'color' => '#1d4ed8', 'bg' => '#eff6ff'],
        'high'   => ['label' => 'Alta',    'color' => '#d97706', 'bg' => '#fffbeb'],
        'urgent' => ['label' => 'Urgente', 'color' => '#dc2626', 'bg' => '#fef2f2'],
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function scopeUnread(Builder $q): Builder
    {
        return $q->whereNull('read_at');
    }

    public function scopeForUser(Builder $q, int $userId): Builder
    {
        return $q->where('user_id', $userId);
    }

    public const ROL_ETIQUETAS = [
        'admin'                => 'Administrador',
        'supervisor'           => 'Supervisor',
        'vendedor'             => 'Vendedor',
        'consultor_digital'    => 'Consultor Digital',
        'consultor_eca'        => 'Consultor ECA',
        'consultor_elt'        => 'Consultor ELT',
        'representante_ventas' => 'Rep. Ventas',
        'coordinador'          => 'Coordinador',
    ];

    /**
     * Normaliza cualquier action_url histórica o de otro servidor (artisan serve, PHP Server de VS Code,
     * producción) para que siempre apunte al host/subruta activos sin generar errores 400/404.
     */
    public static function resolveActionUrl(?string $rawUrl): ?string
    {
        $rawUrl = trim((string) $rawUrl);
        if ($rawUrl === '') {
            return null;
        }

        $parsed = parse_url($rawUrl);
        if ($parsed === false) {
            return null;
        }

        $path = $parsed['path'] ?? '/';
        $query = isset($parsed['query']) && $parsed['query'] !== '' ? '?' . $parsed['query'] : '';
        $fragment = isset($parsed['fragment']) && $parsed['fragment'] !== '' ? '#' . $parsed['fragment'] : '';

        $decodedPath = rawurldecode($path);
        if (preg_match('#/public(?:/index\.php)?(/.*)?$#i', $decodedPath, $m)) {
            $cleanPath = $m[1] ?? '/';
        } elseif (preg_match('#/index\.php(/.*)?$#i', $decodedPath, $m)) {
            $cleanPath = $m[1] ?? '/';
        } else {
            $cleanPath = $path;
        }

        $cleanPath = '/' . ltrim($cleanPath, '/');
        $cleanPath = implode('/', array_map('rawurlencode', explode('/', rawurldecode($cleanPath))));

        $currentBase = '';
        if (app()->bound('request')) {
            $currentBase = rtrim(request()->getBaseUrl(), '/');
        }

        return ($currentBase !== '' ? $currentBase : '') . $cleanPath . $query . $fragment;
    }

    /**
     * Serializa la notificación para consumo uniforme en el modal, toast flotante y Web Notifications.
     */
    public function toClientPayload(): array
    {
        $cat = self::CATEGORIAS[$this->category] ?? [
            'label' => ucfirst(str_replace('_', ' ', (string) $this->category)),
            'icon'  => '🔔',
            'color' => '#475569',
            'bg'    => '#f8fafc',
        ];

        $prio = self::PRIORIDADES[$this->priority] ?? self::PRIORIDADES['normal'];
        $createdAt = $this->created_at ?? now();

        if ($createdAt->isToday()) {
            $dateGroup = 'Hoy';
        } elseif ($createdAt->isYesterday()) {
            $dateGroup = 'Ayer';
        } elseif ($createdAt->greaterThanOrEqualTo(now()->subDays(7))) {
            $dateGroup = 'Esta semana';
        } else {
            $dateGroup = 'Anteriores';
        }

        $actorRole = $this->actor?->roles?->first()?->name ?? ($this->actor_id ? null : 'sistema');
        $actorRoleLabel = $actorRole === 'sistema'
            ? 'Sistema'
            : (self::ROL_ETIQUETAS[$actorRole] ?? ($actorRole ? ucfirst(str_replace('_', ' ', $actorRole)) : 'Equipo SI'));

        return [
            'id'               => $this->id,
            'category'         => $this->category,
            'category_label'   => $cat['label'],
            'category_icon'    => $cat['icon'],
            'category_color'   => $cat['color'],
            'category_bg'      => $cat['bg'],
            'event_type'       => $this->event_type,
            'priority'         => $this->priority,
            'priority_label'   => $prio['label'],
            'priority_color'   => $prio['color'],
            'priority_bg'      => $prio['bg'],
            'icon'             => $this->icon ?: $cat['icon'],
            'title'            => $this->title,
            'message'          => $this->message,
            'details'          => $this->details ?? [],
            'action_url'       => self::resolveActionUrl($this->action_url),
            'action_label'     => $this->action_label ?: 'Ver en el sistema',
            'school_id'        => $this->school_id,
            'school_name'      => $this->school?->name,
            'actor_id'         => $this->actor_id,
            'actor_name'       => $this->actor?->name ?? 'Sistema SIC',
            'actor_role'       => $actorRole,
            'actor_role_label' => $actorRoleLabel,
            'is_unread'        => $this->read_at === null,
            'read_at'          => $this->read_at?->toIso8601String(),
            'created_at'       => $createdAt->toIso8601String(),
            'created_human'    => $createdAt->diffForHumans(),
            'created_exact'    => $createdAt->format('d/m/Y H:i'),
            'date_group'       => $dateGroup,
        ];
    }
}
