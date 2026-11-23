<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $baseQuery = UserNotification::forUser($user->id);

        $unreadCount = (clone $baseQuery)->unread()->count();
        $totalCount = (clone $baseQuery)->count();
        $latestId = (int) ((clone $baseQuery)->max('id') ?? 0);

        $categoryCounts = $this->buildCategoryCounts($user->id);
        $schoolCounts = $this->buildSchoolCounts($user->id);

        $query = UserNotification::forUser($user->id)
            ->with(['actor.roles', 'school:id,name'])
            ->latest('id');

        if ($request->filled('category') && $request->input('category') !== 'all') {
            $query->where('category', $request->input('category'));
        }

        if ($request->input('status') === 'unread') {
            $query->unread();
        } elseif ($request->input('status') === 'read') {
            $query->whereNotNull('read_at');
        }

        if ($request->filled('priority') && $request->input('priority') !== 'all') {
            if ($request->input('priority') === 'important') {
                $query->whereIn('priority', ['high', 'urgent']);
            } else {
                $query->where('priority', $request->input('priority'));
            }
        }

        if ($request->filled('school_id') && $request->input('school_id') !== 'all') {
            $query->where('school_id', (int) $request->input('school_id'));
        }

        if ($request->filled('search')) {
            $term = trim((string) $request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                  ->orWhere('message', 'like', "%{$term}%")
                  ->orWhereHas('school', fn ($sq) => $sq->where('name', 'like', "%{$term}%"))
                  ->orWhereHas('actor', fn ($aq) => $aq->where('name', 'like', "%{$term}%"));
            });
        }

        if ($request->filled('limit')) {
            $limit = min(150, max(10, (int) $request->input('limit')));
        } else {
            $limit = 100;
        }

        $items = $query->limit($limit)->get()->map->toClientPayload()->values();

        return response()->json([
            'unread_count'    => $unreadCount,
            'total_count'     => $totalCount,
            'latest_id'       => $latestId,
            'category_counts' => $categoryCounts,
            'school_counts'   => $schoolCounts,
            'notifications'   => $items,
        ]);
    }

    public function poll(Request $request): JsonResponse
    {
        $user = auth()->user();
        $sinceId = max(0, (int) $request->query('since_id', 0));

        $baseQuery = UserNotification::forUser($user->id);
        $latestId = (int) ((clone $baseQuery)->max('id') ?? 0);
        if ($latestId > 0) {
            Cache::forever("sic_last_seen_notif_id_{$user->id}", $latestId);
        }

        if ($sinceId > 0 && $latestId <= $sinceId) {
            $unreadCount = (clone $baseQuery)->unread()->count();
            return response()->json([
                'unread_count'      => $unreadCount,
                'latest_id'         => $latestId,
                'new_notifications' => [],
            ]);
        }

        $unreadCount = (clone $baseQuery)->unread()->count();
        $totalCount = (clone $baseQuery)->count();

        $newNotifications = [];
        if ($sinceId > 0 && $latestId > $sinceId) {
            $newNotifications = UserNotification::forUser($user->id)
                ->where('id', '>', $sinceId)
                ->with(['actor.roles', 'school:id,name'])
                ->orderBy('id')
                ->limit(15)
                ->get()
                ->map->toClientPayload()
                ->values();
        }

        return response()->json([
            'unread_count'      => $unreadCount,
            'total_count'       => $totalCount,
            'latest_id'         => $latestId,
            'category_counts'   => $this->buildCategoryCounts($user->id),
            'new_notifications' => $newNotifications,
        ]);
    }

    public function markRead(Request $request, UserNotification $notification): JsonResponse
    {
        abort_unless((int) $notification->user_id === (int) auth()->id(), 403);

        if ($request->boolean('toggle') && $notification->read_at !== null) {
            $notification->update(['read_at' => null]);
        } elseif ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }

        $userId = auth()->id();

        return response()->json([
            'ok'              => true,
            'is_unread'       => $notification->read_at === null,
            'unread_count'    => UserNotification::forUser($userId)->unread()->count(),
            'category_counts' => $this->buildCategoryCounts($userId),
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $query = UserNotification::forUser($userId)->unread();
        $this->applyBulkFilters($query, $request);

        $updated = $query->update(['read_at' => now()]);

        return response()->json([
            'ok'              => true,
            'updated'         => $updated,
            'unread_count'    => UserNotification::forUser($userId)->unread()->count(),
            'category_counts' => $this->buildCategoryCounts($userId),
        ]);
    }

    public function destroy(UserNotification $notification): JsonResponse
    {
        abort_unless((int) $notification->user_id === (int) auth()->id(), 403);
        $notification->delete();

        $userId = auth()->id();

        return response()->json([
            'ok'              => true,
            'unread_count'    => UserNotification::forUser($userId)->unread()->count(),
            'total_count'     => UserNotification::forUser($userId)->count(),
            'category_counts' => $this->buildCategoryCounts($userId),
        ]);
    }

    public function clear(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $mode = $request->input('mode', 'read');
        $query = UserNotification::forUser($userId);
        $this->applyBulkFilters($query, $request);

        if ($mode === 'read') {
            $query->whereNotNull('read_at');
        }

        $deleted = $query->delete();

        return response()->json([
            'ok'              => true,
            'deleted'         => $deleted,
            'unread_count'    => UserNotification::forUser($userId)->unread()->count(),
            'total_count'     => UserNotification::forUser($userId)->count(),
            'category_counts' => $this->buildCategoryCounts($userId),
        ]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $user = auth()->user();
        $validCategories = array_keys(UserNotification::CATEGORIAS);

        $data = $request->validate([
            'silent_mode'          => 'sometimes|boolean',
            'compact_mode'         => 'sometimes|boolean',
            'show_floating_banner' => 'sometimes|boolean',
            'banner_duration_sec'  => 'sometimes|integer|in:5,8,12,15',
            'show_login_summary'   => 'sometimes|boolean',
            'windows_desktop'      => 'sometimes|boolean',
            'only_high_priority'   => 'sometimes|boolean',
            'categories'           => 'sometimes|array',
            'categories.*'         => 'boolean',
        ]);

        $prefs = $user->notificationPrefs();

        foreach (['silent_mode', 'compact_mode', 'show_floating_banner', 'show_login_summary', 'windows_desktop', 'only_high_priority'] as $flag) {
            if (array_key_exists($flag, $data)) {
                $prefs[$flag] = (bool) $data[$flag];
            }
        }

        if (array_key_exists('silent_mode', $data)) {
            $prefs['show_floating_banner'] = !$prefs['silent_mode'];
        } elseif (array_key_exists('show_floating_banner', $data)) {
            $prefs['silent_mode'] = !$prefs['show_floating_banner'];
        }

        if (array_key_exists('banner_duration_sec', $data)) {
            $prefs['banner_duration_sec'] = (int) $data['banner_duration_sec'];
        }

        if (isset($data['categories']) && is_array($data['categories'])) {
            foreach ($validCategories as $slug) {
                if (array_key_exists($slug, $data['categories'])) {
                    $prefs['categories'][$slug] = (bool) $data['categories'][$slug];
                }
            }
        }

        $user->notification_settings = $prefs;
        $user->save();

        return response()->json([
            'ok'       => true,
            'settings' => $user->notificationPrefs(),
        ]);
    }

    private function applyBulkFilters($query, Request $request): void
    {
        if ($request->filled('category') && $request->input('category') !== 'all') {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('school_id') && $request->input('school_id') !== 'all') {
            $query->where('school_id', (int) $request->input('school_id'));
        }

        if (!auth()->user()?->hasRole('admin')) {
            return;
        }

        if ($request->filled('actor_role') && $request->input('actor_role') !== 'all') {
            $actorRole = (string) $request->input('actor_role');
            if ($actorRole === 'sistema') {
                $query->whereNull('actor_id');
            } else {
                $query->whereHas('actor.roles', fn ($rq) => $rq->where('name', $actorRole));
            }
        }

        if ($request->filled('actor_name') && $request->input('actor_name') !== 'all') {
            $actorName = (string) $request->input('actor_name');
            if ($actorName === 'Sistema SIC') {
                $query->whereNull('actor_id');
            } else {
                $query->whereHas('actor', fn ($aq) => $aq->where('name', $actorName));
            }
        }
    }

    private function buildCategoryCounts(int $userId): array
    {
        $rows = UserNotification::forUser($userId)
            ->selectRaw('category, COUNT(*) as total, SUM(CASE WHEN read_at IS NULL THEN 1 ELSE 0 END) as unread')
            ->groupBy('category')
            ->get()
            ->keyBy('category');

        $out = [];
        foreach (UserNotification::CATEGORIAS as $slug => $meta) {
            $row = $rows->get($slug);
            $out[$slug] = [
                'label'  => $meta['label'],
                'icon'   => $meta['icon'],
                'color'  => $meta['color'],
                'bg'     => $meta['bg'],
                'total'  => (int) ($row->total ?? 0),
                'unread' => (int) ($row->unread ?? 0),
            ];
        }

        return $out;
    }

    private function buildSchoolCounts(int $userId): array
    {
        return UserNotification::forUser($userId)
            ->whereNotNull('school_id')
            ->join('schools', 'user_notifications.school_id', '=', 'schools.id')
            ->selectRaw('schools.id as id, schools.name as name, COUNT(*) as total, SUM(CASE WHEN user_notifications.read_at IS NULL THEN 1 ELSE 0 END) as unread')
            ->groupBy('schools.id', 'schools.name')
            ->orderByDesc('unread')
            ->orderBy('schools.name')
            ->get()
            ->map(fn ($r) => [
                'id'     => (int) $r->id,
                'name'   => $r->name,
                'total'  => (int) $r->total,
                'unread' => (int) $r->unread,
            ])
            ->values()
            ->all();
    }
}
