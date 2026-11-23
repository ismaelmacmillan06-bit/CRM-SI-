<?php

namespace App\Services;

use App\Http\Controllers\SeguimientoController;
use App\Models\ArchivoSI;
use App\Models\Comunicado;
use App\Models\SeguimientoRecurso;
use App\Models\TareaSI;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SectionNovedadesService
{
    public const LOOKBACK_DAYS = 5;

    /**
     * Resuelve (una sola vez por petición HTTP) las novedades pendientes del apartado activo.
     * Captura los IDs nuevos antes de actualizar sic_seen_* para resaltarlos solo en esa visita.
     */
    public function resolveForCurrentRequest(?User $user = null): ?array
    {
        $req = request();
        if ($req->attributes->has('sic_section_novedades')) {
            return $req->attributes->get('sic_section_novedades');
        }

        $user = $user ?: auth()->user();
        if (!$user) {
            return $this->memoize('guest', null);
        }

        if ($req->routeIs('tablero.*')) {
            return $this->consumeTablero($user);
        }
        if ($req->routeIs('tareas.*')) {
            return $this->consumeTareas($user);
        }
        if ($req->routeIs('seguimiento.show')) {
            return $this->consumeSeguimiento($user, (string) $req->route('plataforma'));
        }
        if ($req->routeIs('herramientas.index')) {
            return $this->consumeHerramientas($user);
        }
        if ($req->routeIs('ssa.index')) {
            return $this->consumeSsa($user);
        }
        if ($req->routeIs('reporte-semanal.index')) {
            return $this->consumeReporteSemanal($user);
        }

        return $this->memoize('none', null);
    }

    public function consumeTablero(User $user): ?array
    {
        $memoKey = 'tablero';
        [$hit, $cached] = $this->getMemoized($memoKey);
        if ($hit) {
            return $cached;
        }

        $key = "sic_seen_tablero_id_{$user->id}";
        $prevId = (int) Cache::get($key, 0);
        $desde = now()->subDays(self::LOOKBACK_DAYS);

        $nuevosIds = Comunicado::visiblePara($user)
            ->activos()
            ->where('created_at', '>=', $desde)
            ->where('id', '>', $prevId)
            ->latest('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $maxId = (int) (Comunicado::visiblePara($user)->activos()->max('id') ?? 0);
        Cache::forever($key, max($prevId, $maxId));

        if (empty($nuevosIds)) {
            return $this->memoize($memoKey, null);
        }

        return $this->memoize($memoKey, [
            'section_key'    => 'tablero',
            'count'          => count($nuevosIds),
            'summary'        => null,
            'highlight_tabs' => [],
            'highlight_ids'  => ['comunicados' => $nuevosIds],
        ]);
    }

    public function consumeTareas(User $user): ?array
    {
        $memoKey = 'tareas';
        [$hit, $cached] = $this->getMemoized($memoKey);
        if ($hit) {
            return $cached;
        }

        $key = "sic_seen_tareas_id_{$user->id}";
        $prevId = (int) Cache::get($key, 0);
        $desde = now()->subDays(self::LOOKBACK_DAYS);

        $nuevasIds = TareaSI::visiblePara($user)
            ->where('created_at', '>=', $desde)
            ->where('id', '>', $prevId)
            ->latest('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $maxId = (int) (TareaSI::visiblePara($user)->max('id') ?? 0);
        Cache::forever($key, max($prevId, $maxId));

        if (empty($nuevasIds)) {
            return $this->memoize($memoKey, null);
        }

        return $this->memoize($memoKey, [
            'section_key'    => 'tareas',
            'count'          => count($nuevasIds),
            'summary'        => null,
            'highlight_tabs' => [],
            'highlight_ids'  => ['tareas' => $nuevasIds],
        ]);
    }

    public function consumeSeguimiento(User $user, string $plataforma): ?array
    {
        $memoKey = "seguimiento:{$plataforma}";
        [$hit, $cached] = $this->getMemoized($memoKey);
        if ($hit) {
            return $cached;
        }

        if (!array_key_exists($plataforma, SeguimientoController::PLATAFORMAS)) {
            return $this->memoize($memoKey, null);
        }

        $recKey = "sic_seen_seg_rec_{$plataforma}_{$user->id}";
        $notifKey = "sic_seen_seg_notif_{$plataforma}_{$user->id}";
        $prevRecId = (int) Cache::get($recKey, 0);
        $prevNotifId = (int) Cache::get($notifKey, 0);
        $desde = now()->subDays(self::LOOKBACK_DAYS);

        $nuevosRecursos = SeguimientoRecurso::visiblePara($user)
            ->where('plataforma', $plataforma)
            ->where('created_at', '>=', $desde)
            ->where('id', '>', $prevRecId)
            ->latest('id')
            ->get(['id', 'titulo', 'categoria']);

        $allRecentUnread = UserNotification::forUser($user->id)
            ->unread()
            ->where('created_at', '>=', $desde)
            ->latest('id')
            ->get(['id', 'title', 'school_id', 'action_url']);

        $pattern = '#/seguimiento/' . preg_quote($plataforma, '#') . '(?:$|[/?\#])#';
        $matchingNotifs = $allRecentUnread->filter(fn (UserNotification $n) => preg_match($pattern, (string) $n->action_url) === 1);
        $nuevasNotifs = $matchingNotifs->filter(fn (UserNotification $n) => (int) $n->id > $prevNotifId)->values();

        $maxRecId = (int) (SeguimientoRecurso::visiblePara($user)
            ->where('plataforma', $plataforma)
            ->where('created_at', '>=', $desde)
            ->max('id') ?? 0);
        $maxNotifId = (int) ($matchingNotifs->max('id') ?? 0);

        Cache::forever($recKey, max($prevRecId, $maxRecId));
        Cache::forever($notifKey, max($prevNotifId, $maxNotifId));

        $resourceTitles = $nuevosRecursos
            ->map(fn ($r) => mb_strtolower((string) $r->titulo))
            ->filter()
            ->values();

        $schoolIds = [];
        $notifsExtra = 0;
        foreach ($nuevasNotifs as $n) {
            $notifTitleLower = mb_strtolower((string) $n->title);
            $matchesResource = $resourceTitles->contains(fn ($rt) => str_contains($notifTitleLower, $rt));
            if ($matchesResource) {
                continue;
            }
            $notifsExtra++;
            if ($n->school_id) {
                $schoolIds[] = (int) $n->school_id;
            }
        }

        $totalCount = $nuevosRecursos->count() + $notifsExtra;
        if ($totalCount === 0) {
            return $this->memoize($memoKey, null);
        }

        return $this->memoize($memoKey, [
            'section_key'    => "seguimiento.{$plataforma}",
            'count'          => $totalCount,
            'summary'        => null,
            'highlight_tabs' => [
                'formatos' => $nuevosRecursos->contains('categoria', 'formatos'),
                'manuales' => $nuevosRecursos->contains('categoria', 'manuales'),
            ],
            'highlight_ids' => [
                'recursos' => $nuevosRecursos->pluck('id')->map(fn ($id) => (int) $id)->all(),
                'schools'  => array_values(array_unique($schoolIds)),
            ],
        ]);
    }

    public function consumeHerramientas(User $user): ?array
    {
        $memoKey = 'herramientas';
        [$hit, $cached] = $this->getMemoized($memoKey);
        if ($hit) {
            return $cached;
        }

        if (!$user->hasAnyRole(['admin', 'consultor_digital'])) {
            return $this->memoize($memoKey, null);
        }

        $recKey = "sic_seen_herramientas_id_{$user->id}";
        $notifKey = "sic_seen_herr_notif_id_{$user->id}";
        $prevRecId = (int) Cache::get($recKey, 0);
        $prevNotifId = (int) Cache::get($notifKey, 0);
        $desde = now()->subDays(self::LOOKBACK_DAYS);

        $nuevosArchivos = ArchivoSI::where('created_at', '>=', $desde)
            ->where('id', '>', $prevRecId)
            ->latest('id')
            ->get(['id', 'titulo']);

        $allHerrNotifs = UserNotification::forUser($user->id)
            ->unread()
            ->where('category', 'herramientas')
            ->where('created_at', '>=', $desde)
            ->latest('id')
            ->get(['id', 'title']);

        $nuevasNotifs = $allHerrNotifs->filter(fn (UserNotification $n) => (int) $n->id > $prevNotifId)->values();

        $maxRecId = (int) (ArchivoSI::where('created_at', '>=', $desde)->max('id') ?? 0);
        $maxNotifId = (int) ($allHerrNotifs->max('id') ?? 0);

        Cache::forever($recKey, max($prevRecId, $maxRecId));
        Cache::forever($notifKey, max($prevNotifId, $maxNotifId));

        $fileTitles = $nuevosArchivos
            ->map(fn ($a) => mb_strtolower((string) $a->titulo))
            ->filter()
            ->values();

        $notifsExtra = $nuevasNotifs->reject(function (UserNotification $n) use ($fileTitles) {
            $t = mb_strtolower((string) $n->title);
            return $fileTitles->contains(fn ($ft) => str_contains($t, $ft));
        })->count();

        $totalCount = $nuevosArchivos->count() + $notifsExtra;
        if ($totalCount === 0) {
            return $this->memoize($memoKey, null);
        }

        return $this->memoize($memoKey, [
            'section_key'    => 'herramientas',
            'count'          => $totalCount,
            'summary'        => null,
            'highlight_tabs' => [],
            'highlight_ids'  => [
                'archivos' => $nuevosArchivos->pluck('id')->map(fn ($id) => (int) $id)->all(),
            ],
        ]);
    }

    public function consumeSsa(User $user): ?array
    {
        $memoKey = 'ssa';
        [$hit, $cached] = $this->getMemoized($memoKey);
        if ($hit) {
            return $cached;
        }

        $ssaKey = "sic_seen_ssa_notif_id_{$user->id}";
        $prevNotifId = (int) Cache::get($ssaKey, 0);
        $desde = now()->subDays(self::LOOKBACK_DAYS);

        $matching = UserNotification::forUser($user->id)
            ->unread()
            ->where('created_at', '>=', $desde)
            ->latest('id')
            ->get(['id', 'school_id', 'action_url'])
            ->filter(fn (UserNotification $n) => preg_match('#/ssa(?:$|[/?\#])#', (string) $n->action_url) === 1);

        $nuevas = $matching->filter(fn (UserNotification $n) => (int) $n->id > $prevNotifId)->values();
        Cache::forever($ssaKey, max($prevNotifId, (int) ($matching->max('id') ?? 0)));

        if ($nuevas->isEmpty()) {
            return $this->memoize($memoKey, null);
        }

        return $this->memoize($memoKey, [
            'section_key'    => 'ssa',
            'count'          => $nuevas->count(),
            'summary'        => null,
            'highlight_tabs' => [],
            'highlight_ids'  => [
                'schools' => $nuevas->pluck('school_id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all(),
            ],
        ]);
    }

    public function consumeReporteSemanal(User $user): ?array
    {
        $memoKey = 'reporte-semanal';
        [$hit, $cached] = $this->getMemoized($memoKey);
        if ($hit) {
            return $cached;
        }

        $repKey = "sic_seen_repsem_notif_id_{$user->id}";
        $prevNotifId = (int) Cache::get($repKey, 0);
        $desde = now()->subDays(self::LOOKBACK_DAYS);

        $allRepNotifs = UserNotification::forUser($user->id)
            ->unread()
            ->where('category', 'reporte_semanal')
            ->where('created_at', '>=', $desde)
            ->latest('id')
            ->get(['id', 'title']);

        $nuevas = $allRepNotifs->filter(fn (UserNotification $n) => (int) $n->id > $prevNotifId)->values();
        Cache::forever($repKey, max($prevNotifId, (int) ($allRepNotifs->max('id') ?? 0)));

        if ($nuevas->isEmpty()) {
            return $this->memoize($memoKey, null);
        }

        return $this->memoize($memoKey, [
            'section_key'    => 'reporte-semanal',
            'count'          => $nuevas->count(),
            'summary'        => Str::limit((string) $nuevas->first()->title, 72),
            'highlight_tabs' => [],
            'highlight_ids'  => [],
        ]);
    }

    private function getMemoized(string $key): array
    {
        $attrs = request()->attributes;
        if ($attrs->get('sic_section_novedades_key') === $key && $attrs->has('sic_section_novedades')) {
            return [true, $attrs->get('sic_section_novedades')];
        }
        return [false, null];
    }

    private function memoize(string $key, ?array $payload): ?array
    {
        $attrs = request()->attributes;
        $attrs->set('sic_section_novedades_key', $key);
        $attrs->set('sic_section_novedades', $payload);
        return $payload;
    }
}
