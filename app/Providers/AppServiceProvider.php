<?php

namespace App\Providers;

use App\Http\Controllers\SeguimientoController;
use App\Models\ArchivoSI;
use App\Models\Comunicado;
use App\Models\Process;
use App\Models\SeguimientoDefinicionAccion;
use App\Models\SeguimientoRecurso;
use App\Models\TareaSI;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\NotificationService;
use App\Services\SectionNovedadesService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Borrar datos del colegio (y editar su ficha general): admin y el consultor digital asignado.
        // Un responsable de plataforma trabaja en el colegio pero no ve estos botones.
        \Illuminate\Support\Facades\Blade::if('puedeGestionar', fn ($school) => \App\Helpers\AccesoColegios::puedeGestionar(auth()->user(), $school));
        \Illuminate\Support\Facades\Blade::if('puedeEliminar', fn ($school) => \App\Helpers\AccesoColegios::puedeEliminar(auth()->user(), $school));

        // En producción el SSL se atiende antes de Laravel: la petición llega como http
        // y los formularios salían con action="http://..." (Chrome: "información no segura").
        if ($this->app->environment('production') || str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // Excel: los textos que empiezan con "=" se guardan como texto, no como fórmula
        \PhpOffice\PhpSpreadsheet\Cell\Cell::setValueBinder(new \App\Helpers\TextoSeguroValueBinder());

        // Optimización SQLite (WAL + synchronous normal) para evitar bloqueos y acelerar lecturas
        if (config('database.default') === 'sqlite') {
            try {
                DB::statement('PRAGMA journal_mode=WAL;');
                DB::statement('PRAGMA synchronous=NORMAL;');
            } catch (\Throwable) {
            }
        }

        View::composer([
            'layouts.app',
            'tareas.index',
            'tablero.index',
            'tablero._card',
            'herramientas.index',
            'seguimiento.dashboard',
            'seguimiento.meta',
            'seguimiento.show',
            'seguimiento.partials._recursos_section',
            'ssa.index',
            'reporte-semanal.index',
        ], function ($view) {
            if (!auth()->check()) {
                return;
            }
            $view->with('sectionNovedades', app(SectionNovedadesService::class)->resolveForCurrentRequest());
        });

        View::composer('layouts.app', function ($view) {
            if (!auth()->check()) {
                return;
            }

            $user = auth()->user();
            $desde = now()->subDays(5);

            $notifUnreadCount = 0;
            $notifLatestId = 0;
            $notifInitialItems = [];
            $notifCategorySummary = [];
            $notifSystemUsers = [];
            $notifPrefs = User::defaultNotificationPrefs();
            $mostrarResumenLoginNotif = false;
            $prevSeenId = session()->has('login_prev_seen_notif_id')
                ? (int) session('login_prev_seen_notif_id')
                : null;
            $hayComunicadoNuevo = false;
            $hayTareaNueva = false;
            $novedadesSeguimiento = [];
            $haySeguimientoNuevo = false;
            $hayHerramientaNueva = false;
            $haySsaNuevo = false;
            $hayReporteSemanalNuevo = false;
            $conteoAccionesSeguimiento = [];
            $arranqueGlobalProcesses = collect();
            $arranqueSeguimientoCatalogo = collect();

            $sectionNovedades = null;
            try {
                $notifPrefs = $user->notificationPrefs();
                $mostrarResumenLoginNotif = (bool) session('show_login_notifications_summary', false)
                    && !$notifPrefs['silent_mode']
                    && $notifPrefs['show_login_summary'];

                try {
                    app(NotificationService::class)->ensureUpcomingTrainingNotifications();
                } catch (\Throwable) {
                }

                // Asegura capturar novedades antes de leer las claves sic_seen_* del menú
                $sectionNovedades = app(SectionNovedadesService::class)->resolveForCurrentRequest($user);

                $notifBase = UserNotification::forUser($user->id);
                $notifUnreadCount = (clone $notifBase)->unread()->count();
                $notifLatestId = (int) ((clone $notifBase)->max('id') ?? 0);

                $notifInitialItems = (clone $notifBase)
                    ->with(['actor.roles', 'school:id,name'])
                    ->latest('id')
                    ->limit(120)
                    ->get()
                    ->map->toClientPayload()
                    ->values()
                    ->all();

                if ($notifUnreadCount > 0) {
                    $rows = (clone $notifBase)
                        ->unread()
                        ->selectRaw('category, COUNT(*) as total')
                        ->groupBy('category')
                        ->pluck('total', 'category');

                    foreach (UserNotification::CATEGORIAS as $slug => $meta) {
                        $count = (int) ($rows[$slug] ?? 0);
                        if ($count > 0) {
                            $notifCategorySummary[] = [
                                'slug'  => $slug,
                                'label' => $meta['label'],
                                'icon'  => $meta['icon'],
                                'count' => $count,
                            ];
                        }
                    }
                }

                if ($user->hasRole('admin')) {
                    $notifSystemUsers = Cache::remember('sic_notif_system_users_v1', 120, function () {
                        return User::with('roles:id,name')
                            ->orderBy('name')
                            ->get(['id', 'name'])
                            ->map(function ($u) {
                                $role = $u->roles->first()?->name;
                                return [
                                    'id'         => (int) $u->id,
                                    'name'       => $u->name,
                                    'role'       => $role,
                                    'role_label' => UserNotification::ROL_ETIQUETAS[$role] ?? ($role ? ucfirst(str_replace('_', ' ', $role)) : 'Equipo SI'),
                                ];
                            })
                            ->values()
                            ->all();
                    });
                }

                if ($notifLatestId > 0) {
                    Cache::forever("sic_last_seen_notif_id_{$user->id}", $notifLatestId);
                }

                if (request()->routeIs('tablero.*')) {
                    $hayComunicadoNuevo = false;
                } else {
                    $lastSeenTableroId = (int) Cache::get("sic_seen_tablero_id_{$user->id}", 0);
                    $hayComunicadoNuevo = Comunicado::visiblePara($user)
                        ->activos()
                        ->where('created_at', '>=', $desde)
                        ->where('id', '>', $lastSeenTableroId)
                        ->exists();
                }

                if (request()->routeIs('tareas.*')) {
                    $hayTareaNueva = false;
                } else {
                    $lastSeenTareaId = (int) Cache::get("sic_seen_tareas_id_{$user->id}", 0);
                    $hayTareaNueva = TareaSI::visiblePara($user)
                        ->where('created_at', '>=', $desde)
                        ->where('id', '>', $lastSeenTareaId)
                        ->exists();
                }

                $recentUnreadNotifs = (clone $notifBase)
                    ->unread()
                    ->where('created_at', '>=', $desde)
                    ->get(['id', 'category', 'action_url']);

                $latestRecByPlatform = SeguimientoRecurso::visiblePara($user)
                    ->where('created_at', '>=', $desde)
                    ->selectRaw('plataforma, MAX(id) as max_id')
                    ->groupBy('plataforma')
                    ->pluck('max_id', 'plataforma');

                $activeSegSlug = request()->routeIs('seguimiento.show')
                    ? (string) request()->route('plataforma')
                    : null;

                foreach (array_keys(SeguimientoController::PLATAFORMAS) as $segSlug) {
                    $maxSegRecId = (int) ($latestRecByPlatform[$segSlug] ?? 0);
                    $maxSegNotifId = (int) ($recentUnreadNotifs
                        ->filter(fn ($n) => preg_match('#/seguimiento/' . preg_quote($segSlug, '#') . '(?:$|[/?\#])#', (string) $n->action_url) === 1)
                        ->max('id') ?? 0);

                    $recKey = "sic_seen_seg_rec_{$segSlug}_{$user->id}";
                    $notifKey = "sic_seen_seg_notif_{$segSlug}_{$user->id}";

                    if ($activeSegSlug === $segSlug) {
                        $novedadesSeguimiento[$segSlug] = false;
                    } else {
                        $novedadesSeguimiento[$segSlug] = ($maxSegRecId > (int) Cache::get($recKey, 0))
                            || ($maxSegNotifId > (int) Cache::get($notifKey, 0));
                    }
                }
                $haySeguimientoNuevo = in_array(true, $novedadesSeguimiento, true);

                if ($user->hasAnyRole(['admin', 'consultor_digital'])) {
                    $maxHerrRecId = (int) (ArchivoSI::where('created_at', '>=', $desde)->max('id') ?? 0);
                    $maxHerrNotifId = (int) ($recentUnreadNotifs->where('category', 'herramientas')->max('id') ?? 0);
                    $herrRecKey = "sic_seen_herramientas_id_{$user->id}";
                    $herrNotifKey = "sic_seen_herr_notif_id_{$user->id}";

                    if (request()->routeIs('herramientas.index')) {
                        $hayHerramientaNueva = false;
                    } else {
                        $hayHerramientaNueva = ($maxHerrRecId > (int) Cache::get($herrRecKey, 0))
                            || ($maxHerrNotifId > (int) Cache::get($herrNotifKey, 0));
                    }
                }

                $maxSsaNotifId = (int) ($recentUnreadNotifs
                    ->filter(fn ($n) => preg_match('#/ssa(?:$|[/?\#])#', (string) $n->action_url) === 1)
                    ->max('id') ?? 0);
                $ssaKey = "sic_seen_ssa_notif_id_{$user->id}";
                if (request()->routeIs('ssa.index')) {
                    $haySsaNuevo = false;
                } else {
                    $haySsaNuevo = $maxSsaNotifId > (int) Cache::get($ssaKey, 0);
                }

                $maxRepNotifId = (int) ($recentUnreadNotifs->where('category', 'reporte_semanal')->max('id') ?? 0);
                $repKey = "sic_seen_repsem_notif_id_{$user->id}";
                if (request()->routeIs('reporte-semanal.index')) {
                    $hayReporteSemanalNuevo = false;
                } else {
                    $hayReporteSemanalNuevo = $maxRepNotifId > (int) Cache::get($repKey, 0);
                }

                $rawConteosSeg = SeguimientoDefinicionAccion::selectRaw('plataforma, COUNT(*) as total')
                    ->groupBy('plataforma')
                    ->pluck('total', 'plataforma');
                foreach (array_keys(SeguimientoController::PLATAFORMAS) as $segSlug) {
                    $conteoAccionesSeguimiento[$segSlug] = (int) ($rawConteosSeg[$segSlug] ?? 0);
                }

                if ($user->hasRole('admin')) {
                    $arranqueGlobalProcesses = Process::orderBy('order')
                        ->orderBy('id')
                        ->get(['id', 'name', 'slug', 'order']);
                    $arranqueSeguimientoCatalogo = SeguimientoDefinicionAccion::orderBy('orden')
                        ->orderBy('id')
                        ->get(['id', 'plataforma', 'clave', 'etiqueta', 'orden'])
                        ->groupBy('plataforma');
                }
            } catch (\Throwable $e) {
                report($e);
            }

            $view->with([
                'sectionNovedades'           => $sectionNovedades,
                'hayComunicadoNuevo'         => $hayComunicadoNuevo,
                'hayTareaNueva'              => $hayTareaNueva,
                'novedadesSeguimiento'       => $novedadesSeguimiento,
                'haySeguimientoNuevo'        => $haySeguimientoNuevo,
                'hayHerramientaNueva'        => $hayHerramientaNueva,
                'haySsaNuevo'                => $haySsaNuevo,
                'hayReporteSemanalNuevo'     => $hayReporteSemanalNuevo,
                'conteoAccionesSeguimiento'  => $conteoAccionesSeguimiento,
                'arranqueGlobalProcesses'    => $arranqueGlobalProcesses,
                'arranqueSeguimientoCatalogo'=> $arranqueSeguimientoCatalogo,
                'notifUnreadCount'          => $notifUnreadCount,
                'notifLatestId'             => $notifLatestId,
                'notifCategorySummary'      => $notifCategorySummary,
                'notifInitialItems'         => $notifInitialItems,
                'notifSystemUsers'          => $notifSystemUsers,
                'notifPrefs'                => $notifPrefs,
                'mostrarResumenLoginNotif'  => $mostrarResumenLoginNotif,
                'notifPrevSeenId'           => $prevSeenId,
            ]);
        });
    }
}
