<?php

namespace App\Services;

use App\Http\Controllers\SeguimientoController;
use App\Models\ArchivoSI;
use App\Models\Comunicado;
use App\Models\Consultant;
use App\Models\School;
use App\Models\SchoolConsultant;
use App\Models\SeguimientoMeta;
use App\Models\SeguimientoRecurso;
use App\Models\SsaCapacitacion;
use App\Models\TareaSI;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\WeeklyReportEntry;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    /**
     * Crea notificaciones para los destinatarios resueltos evitando duplicados,
     * excluyendo al usuario que realizó la acción y respetando la configuración
     * personal de cada destinatario (categorías activas y prioridad mínima).
     */
    public function sendToUsers(array $userIds, array $payload, bool $excludeActor = true): int
    {
        $actorId = array_key_exists('actor_id', $payload) ? $payload['actor_id'] : auth()->id();
        $ids = collect($userIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return 0;
        }

        if ($excludeActor && $actorId) {
            $ids = $ids->reject(fn (int $id) => $id === (int) $actorId)->values();
        }

        if ($ids->isEmpty()) {
            return 0;
        }

        $category = (string) ($payload['category'] ?? 'recordatorios');
        $priority = (string) ($payload['priority'] ?? 'normal');

        try {
            $usersById = User::whereIn('id', $ids)->get(['id', 'notification_settings'])->keyBy('id');
            $filteredIds = $ids->filter(function (int $userId) use ($usersById, $category, $priority) {
                $user = $usersById->get($userId);
                if (!$user) {
                    return false;
                }
                $prefs = $user->notificationPrefs();
                if (isset($prefs['categories'][$category]) && !$prefs['categories'][$category]) {
                    return false;
                }
                if (!empty($prefs['only_high_priority']) && !in_array($priority, ['high', 'urgent'], true)) {
                    return false;
                }
                return true;
            })->values();

            if ($filteredIds->isEmpty()) {
                return 0;
            }

            $now = now();
            $detailsJson = isset($payload['details']) ? json_encode($payload['details'], JSON_UNESCAPED_UNICODE) : null;

            $rows = $filteredIds->map(fn (int $userId) => [
                'user_id'      => $userId,
                'actor_id'     => $actorId,
                'school_id'    => $payload['school_id'] ?? null,
                'category'     => $category,
                'event_type'   => $payload['event_type'],
                'priority'     => $priority,
                'icon'         => $payload['icon'] ?? '🔔',
                'title'        => $payload['title'],
                'message'      => $payload['message'],
                'details'      => $detailsJson,
                'action_url'   => UserNotification::resolveActionUrl($payload['action_url'] ?? null),
                'action_label' => $payload['action_label'] ?? 'Ver detalles',
                'read_at'      => null,
                'created_at'   => $now,
                'updated_at'   => $now,
            ])->all();

            foreach (array_chunk($rows, 200) as $chunk) {
                DB::table('user_notifications')->insert($chunk);
            }

            return count($rows);
        } catch (\Throwable $e) {
            report($e);
            return 0;
        }
    }

    public function adminIds(): array
    {
        return User::role('admin')->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    public function userIdsByRoles(array $roles): array
    {
        if (in_array('todos', $roles, true)) {
            return User::pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        $validRoles = array_values(array_filter($roles));
        if (empty($validRoles)) {
            return [];
        }

        return User::whereHas('roles', fn ($q) => $q->whereIn('name', $validRoles))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function userIdsForSchool(
        School|int $school,
        ?array $onlySchoolRoles = null,
        ?int $extraConsultantId = null,
        array $extraRoles = []
    ): array {
        $schoolModel = $school instanceof School ? $school : School::find($school);
        $ids = $this->adminIds();

        if ($schoolModel) {
            if ($schoolModel->consultant_id) {
                $uid = Consultant::whereKey($schoolModel->consultant_id)->value('user_id');
                if ($uid) {
                    $ids[] = (int) $uid;
                }
            }

            $scQuery = SchoolConsultant::query()
                ->where('school_id', $schoolModel->id)
                ->join('consultants', 'school_consultants.consultant_id', '=', 'consultants.id');

            if ($onlySchoolRoles !== null && !empty($onlySchoolRoles)) {
                $scQuery->whereIn('school_consultants.role', $onlySchoolRoles);
            }

            $ids = array_merge($ids, $scQuery->pluck('consultants.user_id')->all());
        }

        if ($extraConsultantId) {
            $uid = Consultant::whereKey($extraConsultantId)->value('user_id');
            if ($uid) {
                $ids[] = (int) $uid;
            }
        }

        if (!empty($extraRoles)) {
            $ids = array_merge($ids, $this->userIdsByRoles($extraRoles));
        }

        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }

    // ── Notificaciones activas del CRM ────────────────────────────────────────

    public function notifyTareaCreated(TareaSI $tarea, int $colegiosCount, bool $isUpdate = false): int
    {
        $rolesPorAudiencia = [
            'digital'   => ['admin', 'consultor_digital'],
            'eca'       => ['admin', 'consultor_eca'],
            'elt'       => ['admin', 'consultor_elt'],
            'academico' => ['admin', 'consultor_eca', 'consultor_elt'],
        ];

        $roles = $rolesPorAudiencia[$tarea->audiencia] ?? ['admin'];
        $audienciaLabel = TareaSI::AUDIENCIAS[$tarea->audiencia] ?? $tarea->audiencia;
        $recipients = $tarea->esAcademica()
            ? $this->destinatariosTareaAcademica($tarea)
            : $this->userIdsByRoles($roles);
        $accionTxt = $isUpdate ? 'Tarea SI actualizada' : 'Nueva tarea SI asignada';

        return $this->sendToUsers($recipients, [
            'category'     => 'tareas',
            'event_type'   => $isUpdate ? 'tarea_updated' : 'tarea_created',
            'priority'     => 'high',
            'icon'         => '✅',
            'title'        => "{$accionTxt}: {$tarea->titulo}",
            'message'      => "Dirigida a {$audienciaLabel}" . ($colegiosCount > 0 ? " ({$colegiosCount} colegio(s))." : '.'),
            'details'      => [
                'Título'             => $tarea->titulo,
                'Audiencia'          => $audienciaLabel,
                'Colegios incluidos' => (string) $colegiosCount,
                'Descripción'        => $tarea->descripcion ?: 'Sin descripción adicional',
            ],
            'action_url'   => route('tareas.index', ['tarea_id' => $tarea->id]),
            'action_label' => 'Ir a Tareas SI',
        ]);
    }

    /**
     * Tareas ECA / ELT / académicas: admin y coordinador (que las gestionan) más solo los
     * consultores ECA/ELT asignados con esa área a alguno de los colegios de la tarea.
     * Así nadie recibe avisos de tareas en las que no tiene colegios que marcar.
     */
    private function destinatariosTareaAcademica(TareaSI $tarea): array
    {
        $rolSpatiePorArea = ['eca' => 'consultor_eca', 'elt' => 'consultor_elt'];
        $schoolIds = $tarea->colegios()->pluck('school_id');

        $ids = $this->userIdsByRoles(['admin', 'coordinador']);

        foreach ($tarea->rolesColegio() as $area) {
            if (!isset($rolSpatiePorArea[$area]) || $schoolIds->isEmpty()) {
                continue;
            }
            $userIds = SchoolConsultant::query()
                ->whereIn('school_consultants.school_id', $schoolIds)
                ->where('school_consultants.role', $area)
                ->join('consultants', 'school_consultants.consultant_id', '=', 'consultants.id')
                ->pluck('consultants.user_id')
                ->unique();

            $ids = array_merge($ids, User::role($rolSpatiePorArea[$area])->whereIn('id', $userIds)->pluck('id')->all());
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    public function notifyComunicadoPublished(Comunicado $comunicado, array $audienciaRoles, bool $isUpdate = false): int
    {
        $roles = in_array('todos', $audienciaRoles, true) ? ['todos'] : $audienciaRoles;
        $recipients = array_unique(array_merge($this->adminIds(), $this->userIdsByRoles($roles)));

        $audienciaTexto = collect($roles)
            ->map(fn ($r) => Comunicado::AUDIENCIAS[$r] ?? $r)
            ->implode(', ');

        $prefijo = $isUpdate ? 'Audiencia actualizada en Tablero SI' : 'Nuevo comunicado en Tablero SI';

        return $this->sendToUsers($recipients, [
            'category'     => 'tablero',
            'event_type'   => $isUpdate ? 'comunicado_updated' : 'comunicado_created',
            'priority'     => 'high',
            'icon'         => '📌',
            'title'        => "{$prefijo}: {$comunicado->titulo}",
            'message'      => "Dirigido a: {$audienciaTexto}"
                . ($comunicado->fecha_termino ? ' • Vigente hasta ' . $comunicado->fecha_termino->format('d/m/Y') : ''),
            'details'      => [
                'Título'    => $comunicado->titulo,
                'Audiencia' => $audienciaTexto,
                'Vigencia'  => $comunicado->fecha_termino?->format('d/m/Y') ?? 'Sin fecha límite',
                'Adjunto'   => $comunicado->archivo_nombre ?? 'Sin archivo adjunto',
                'Enlace'    => $comunicado->enlace ?? 'Sin enlace externo',
                'Contenido' => $comunicado->descripcion,
            ],
            'action_url'   => route('tablero.index'),
            'action_label' => 'Abrir Tablero SI',
        ]);
    }

    public function notifySeguimientoRecursoUploaded(SeguimientoRecurso $recurso, array $audienciaRoles, bool $isUpdate = false): int
    {
        $roles = in_array('todos', $audienciaRoles, true) ? ['todos'] : $audienciaRoles;
        $recipients = array_unique(array_merge($this->adminIds(), $this->userIdsByRoles($roles)));

        $plataformaNombre = SeguimientoController::PLATAFORMAS[$recurso->plataforma]['nombre'] ?? ucfirst($recurso->plataforma);
        $categoriaEtiqueta = $recurso->categoria === 'formatos' ? 'Formato' : 'Manual';
        $audienciaTexto = collect($roles)
            ->map(fn ($r) => SeguimientoRecurso::AUDIENCIAS[$r] ?? $r)
            ->implode(', ');

        $verb = $isUpdate ? 'actualizado' : 'subido';

        return $this->sendToUsers($recipients, [
            'category'     => 'manuales',
            'event_type'   => $isUpdate ? 'manual_updated' : 'manual_uploaded',
            'priority'     => 'normal',
            'icon'         => $recurso->categoria === 'formatos' ? '📄' : '📚',
            'title'        => "{$categoriaEtiqueta} {$verb} en {$plataformaNombre}: {$recurso->titulo}",
            'message'      => "Archivo {$recurso->archivo_nombre} ({$recurso->tamanoFormateado()}) disponible para {$audienciaTexto}.",
            'details'      => [
                'Plataforma'  => $plataformaNombre,
                'Tipo'        => $categoriaEtiqueta,
                'Recurso'     => $recurso->titulo,
                'Archivo'     => $recurso->archivo_nombre . ' (' . strtoupper((string) $recurso->extension) . ', ' . $recurso->tamanoFormateado() . ')',
                'Audiencia'   => $audienciaTexto,
                'Descripción' => $recurso->descripcion ?: 'Sin descripción adicional',
            ],
            'action_url'   => route('seguimiento.show', $recurso->plataforma),
            'action_label' => "Ir a {$plataformaNombre}",
        ]);
    }

    public function notifyArchivoSiUploaded(ArchivoSI $archivo, bool $isUpdate = false): int
    {
        $recipients = $this->userIdsByRoles(['admin', 'consultor_digital']);
        $accion = $isUpdate ? 'actualizado' : 'publicado';

        return $this->sendToUsers($recipients, [
            'category'     => 'herramientas',
            'event_type'   => $isUpdate ? 'herramienta_updated' : 'herramienta_uploaded',
            'priority'     => 'normal',
            'icon'         => '🛠️',
            'title'        => "Archivo {$accion} en Herramientas SI: {$archivo->titulo}",
            'message'      => $archivo->archivo_nombre
                ? "Documento: {$archivo->archivo_nombre}"
                : 'Nuevo recurso disponible en Herramientas SI.',
            'details'      => [
                'Título'      => $archivo->titulo,
                'Archivo'     => $archivo->archivo_nombre ?? 'Sin archivo adjunto',
                'Descripción' => $archivo->descripcion ?: 'Sin descripción',
            ],
            'action_url'   => route('herramientas.index'),
            'action_label' => 'Abrir Herramientas SI',
        ]);
    }

    public function notifySsaEvent(SsaCapacitacion $cap, School $school, bool $isUpdate = false): int
    {
        $recipients = $this->userIdsForSchool($school);
        $tipoLabel = strtoupper($cap->tipo);
        $fechaStr = $cap->fecha ? $cap->fecha->format('d/m/Y') : 'Sin fecha';

        return $this->sendToUsers($recipients, [
            'school_id'    => $school->id,
            'category'     => 'recordatorios',
            'event_type'   => $isUpdate ? 'ssa_updated' : 'ssa_created',
            'priority'     => 'normal',
            'icon'         => '📅',
            'title'        => ($isUpdate ? "Capacitación SSA {$tipoLabel} actualizada" : "Nueva capacitación SSA {$tipoLabel}") . " — {$school->name}",
            'message'      => "Fecha: {$fechaStr}" . ($cap->hora ? " • {$cap->hora}" : '') . " • Estatus: " . ucfirst($cap->estatus),
            'details'      => [
                'Colegio' => $school->name,
                'Área'    => $tipoLabel,
                'Fecha'   => $fechaStr,
                'Hora'    => $cap->hora ?: 'Por definir',
                'Estatus' => ucfirst($cap->estatus),
                'Notas'   => $cap->notas ?: 'Sin notas',
            ],
            'action_url'   => route('ssa.index'),
            'action_label' => 'Abrir Calendario SSA',
        ]);
    }

    public function notifyWeeklyReportUpdated(
        string $weekStart,
        string $weekLabel,
        int $celdasGuardadas,
        array $categoriasTocadas,
        array $consultorUserIds = []
    ): int {
        $recipients = $this->adminIds();
        $catsTxt = !empty($categoriasTocadas) ? implode(', ', $categoriasTocadas) : 'Novedades semanales';
        $actorName = auth()->user()?->name ?? 'Un consultor digital';

        return $this->sendToUsers($recipients, [
            'category'     => 'reporte_semanal',
            'event_type'   => 'reporte_semanal_updated',
            'priority'     => 'normal',
            'icon'         => '🗓️',
            'title'        => "Reporte Semanal completado por {$actorName} ({$weekLabel})",
            'message'      => "{$actorName} registró {$celdasGuardadas} novedad(es) en: {$catsTxt}.",
            'details'      => [
                'Consultor'  => $actorName,
                'Semana'     => $weekLabel,
                'Categorías' => $catsTxt,
                'Celdas'     => (string) $celdasGuardadas,
            ],
            'action_url'   => route('reporte-semanal.index', ['week' => $weekStart]),
            'action_label' => 'Ver Reporte Semanal',
        ]);
    }

    /**
     * Recordatorios automáticos en caché de 2 min:
     * 1) Capacitaciones próximas en SSA y Meta (próximos 3 días).
     * 2) Doble recordatorio los viernes antes de las 12:00 para llenar el Reporte Semanal.
     */
    public function ensureUpcomingTrainingNotifications(): void
    {
        Cache::remember('sic_upcoming_reminders_v2', 120, function () {
            $hoy = today();
            $limite = today()->addDays(3);

            // 1. Capacitaciones SSA próximas
            $ssaCaps = SsaCapacitacion::with('school')
                ->whereIn('estatus', ['pendiente', 'confirmado'])
                ->whereDate('fecha', '>=', $hoy->toDateString())
                ->whereDate('fecha', '<=', $limite->toDateString())
                ->get();

            foreach ($ssaCaps as $cap) {
                if (!$cap->school) {
                    continue;
                }
                $fechaStr = $cap->fecha->format('d/m/Y');
                $dias = (int) $hoy->diffInDays($cap->fecha->copy()->startOfDay(), false);
                $cuando = $dias === 0 ? 'HOY' : ($dias === 1 ? 'MAÑANA' : "en {$dias} días");
                $tipo = strtoupper($cap->tipo);
                $title = "Capacitación SSA {$tipo} {$cuando} ({$fechaStr}) — {$cap->school->name}";

                $recipients = $this->userIdsForSchool($cap->school);
                $this->sendDeduplicatedReminder($recipients, [
                    'school_id'    => $cap->school_id,
                    'category'     => 'recordatorios',
                    'event_type'   => 'capacitacion_proxima',
                    'priority'     => $dias <= 1 ? 'urgent' : 'high',
                    'icon'         => '⏰',
                    'title'        => $title,
                    'message'      => "Capacitación programada para el {$fechaStr}" . ($cap->hora ? " a las {$cap->hora}" : '') . " ({$cap->estatus}).",
                    'details'      => [
                        'Colegio' => $cap->school->name,
                        'Área'    => $tipo,
                        'Fecha'   => $fechaStr,
                        'Hora'    => $cap->hora ?: 'Por definir',
                        'Estatus' => ucfirst($cap->estatus),
                    ],
                    'action_url'   => route('ssa.index'),
                    'action_label' => 'Ver Calendario SSA',
                ]);
            }

            // 2. Fechas de capacitación y aplicación Meta próximas
            $metas = SeguimientoMeta::with('school')
                ->where(function ($q) use ($hoy, $limite) {
                    $q->where(fn ($sub) => $sub
                        ->whereDate('fecha_capacitacion', '>=', $hoy->toDateString())
                        ->whereDate('fecha_capacitacion', '<=', $limite->toDateString()))
                      ->orWhere(fn ($sub) => $sub
                        ->whereDate('fecha_aplicacion', '>=', $hoy->toDateString())
                        ->whereDate('fecha_aplicacion', '<=', $limite->toDateString()));
                })
                ->get();

            foreach ($metas as $meta) {
                if (!$meta->school) {
                    continue;
                }
                $recipients = $this->userIdsForSchool($meta->school);

                if ($meta->fecha_capacitacion && $meta->fecha_capacitacion->between($hoy, $limite)) {
                    $fechaStr = $meta->fecha_capacitacion->format('d/m/Y');
                    $dias = (int) $hoy->diffInDays($meta->fecha_capacitacion->copy()->startOfDay(), false);
                    $cuando = $dias === 0 ? 'HOY' : ($dias === 1 ? 'MAÑANA' : "en {$dias} días");
                    $this->sendDeduplicatedReminder($recipients, [
                        'school_id'    => $meta->school_id,
                        'category'     => 'recordatorios',
                        'event_type'   => 'capacitacion_proxima',
                        'priority'     => $dias <= 1 ? 'urgent' : 'high',
                        'icon'         => '🎯',
                        'title'        => "Capacitación Meta {$cuando} ({$fechaStr}) — {$meta->school->name}",
                        'message'      => 'Fecha próxima de capacitación Meta' . ($meta->horaCorta() ? " a las {$meta->horaCorta()}" : '') . '.',
                        'details'      => [
                            'Plataforma' => 'Meta',
                            'Colegio'    => $meta->school->name,
                            'Fecha'      => $fechaStr,
                            'Horario'    => $meta->horaCorta() ?: 'Por definir',
                            'Detalle'    => $meta->capacitacion ?: 'Capacitación Meta programada',
                        ],
                        'action_url'   => route('seguimiento.show', 'meta'),
                        'action_label' => 'Ver agenda Meta',
                    ]);
                }

                if ($meta->fecha_aplicacion && $meta->fecha_aplicacion->between($hoy, $limite)) {
                    $fechaStr = $meta->fecha_aplicacion->format('d/m/Y');
                    $dias = (int) $hoy->diffInDays($meta->fecha_aplicacion->copy()->startOfDay(), false);
                    $cuando = $dias === 0 ? 'HOY' : ($dias === 1 ? 'MAÑANA' : "en {$dias} días");
                    $this->sendDeduplicatedReminder($recipients, [
                        'school_id'    => $meta->school_id,
                        'category'     => 'recordatorios',
                        'event_type'   => 'capacitacion_proxima',
                        'priority'     => $dias <= 1 ? 'urgent' : 'high',
                        'icon'         => '🎯',
                        'title'        => "Aplicación Meta {$cuando} ({$fechaStr}) — {$meta->school->name}",
                        'message'      => "Fecha próxima de aplicación de examen Meta en {$meta->school->name}.",
                        'details'      => [
                            'Plataforma' => 'Meta',
                            'Colegio'    => $meta->school->name,
                            'Fecha'      => $fechaStr,
                            'Detalle'    => 'Aplicación de evaluación Meta',
                        ],
                        'action_url'   => route('seguimiento.show', 'meta'),
                        'action_label' => 'Ver agenda Meta',
                    ]);
                }
            }

            // 3. Doble recordatorio de Reporte Semanal los viernes antes de las 12:00
            $this->checkFridayWeeklyReportReminders(now());

            return true;
        });
    }

    /**
     * Genera hasta 2 recordatorios los viernes antes de las 12:00 para consultores digitales
     * que aún no han llenado su Reporte Semanal de la semana en curso.
     */
    public function checkFridayWeeklyReportReminders(?CarbonInterface $now = null): void
    {
        $now = $now ? Carbon::instance($now) : now();
        if (!$now->isFriday()) {
            return;
        }

        $weekStart = $now->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $fridayStr = $now->format('d/m/Y');

        $filledConsultantIds = WeeklyReportEntry::whereDate('week_start', $weekStart)
            ->get(['consultant_id', 'content'])
            ->filter(fn ($entry) => trim((string) $entry->content) !== '')
            ->pluck('consultant_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->all();

        $pendingUsers = User::role('consultor_digital')
            ->with('consultant')
            ->get()
            ->filter(function (User $u) use ($filledConsultantIds) {
                $cid = $u->consultant?->id;
                return !$cid || !in_array((int) $cid, $filledConsultantIds, true);
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (empty($pendingUsers)) {
            return;
        }

        $url = route('reporte-semanal.index', ['week' => $weekStart]);

        // 1er aviso: durante la mañana del viernes antes de las 12:00
        if ($now->hour < 12) {
            $this->sendDeduplicatedReminder($pendingUsers, [
                'category'     => 'reporte_semanal',
                'event_type'   => 'reporte_semanal_viernes_1',
                'priority'     => 'high',
                'icon'         => '🗓️',
                'title'        => "1er Recordatorio: Reporte Semanal antes de las 12:00 p.m. ({$fridayStr})",
                'message'      => 'Hoy es viernes. Recuerda capturar tus novedades del Reporte Semanal antes del mediodía.',
                'details'      => [
                    'Viernes'      => $fridayStr,
                    'Fecha límite' => 'Hoy antes de las 12:00 p.m.',
                    'Semana'       => $weekStart,
                    'Recordatorio' => 'Aviso 1 de 2',
                ],
                'action_url'   => $url,
                'action_label' => 'Ir al Reporte Semanal',
            ]);
        }

        // 2do aviso: entre las 10:00 y antes de terminar el margen (10:00 - 12:59) si sigue pendiente
        if ($now->hour >= 10 && $now->hour < 13) {
            $this->sendDeduplicatedReminder($pendingUsers, [
                'category'     => 'reporte_semanal',
                'event_type'   => 'reporte_semanal_viernes_2',
                'priority'     => 'urgent',
                'icon'         => '⏰',
                'title'        => "2do Aviso: Llenar Reporte Semanal antes de las 12:00 p.m. ({$fridayStr})",
                'message'      => 'Tu columna del Reporte Semanal de esta semana sigue pendiente. Complétala antes de las 12:00 p.m.',
                'details'      => [
                    'Viernes'      => $fridayStr,
                    'Fecha límite' => '12:00 p.m.',
                    'Semana'       => $weekStart,
                    'Recordatorio' => 'Aviso 2 de 2 (Urgente)',
                ],
                'action_url'   => $url,
                'action_label' => 'Llenar Reporte Semanal',
            ]);
        }
    }

    private function sendDeduplicatedReminder(array $userIds, array $payload): int
    {
        $ids = collect($userIds)->filter()->map(fn ($id) => (int) $id)->unique()->values();
        if ($ids->isEmpty()) {
            return 0;
        }

        $yaTienen = UserNotification::whereIn('user_id', $ids)
            ->where('event_type', $payload['event_type'])
            ->where('title', $payload['title'])
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $pendientes = $ids->reject(fn (int $id) => in_array($id, $yaTienen, true))->all();
        if (empty($pendientes)) {
            return 0;
        }

        $payload['actor_id'] = null;
        return $this->sendToUsers($pendientes, $payload, false);
    }
}
