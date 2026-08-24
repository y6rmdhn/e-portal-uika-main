<?php

namespace App\Services;

use App\Models\UserActivityLog;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class ActivityLogService
{
    const TYPE_LOGIN            = 'login';
    const TYPE_LOGOUT           = 'logout';
    const TYPE_UPDATE_PROFILE   = 'update_profile';
    const TYPE_CHANGE_PASSWORD  = 'change_password';
    const TYPE_RESET_PASSWORD   = 'reset_password';
    const TYPE_APP_ACCESS       = 'app_access';

    // Unit CRUD
    const TYPE_UNIT_CREATE      = 'unit_create';
    const TYPE_UNIT_UPDATE      = 'unit_update';
    const TYPE_UNIT_DELETE      = 'unit_delete';

    // Unit assignments
    const TYPE_UNIT_ASSIGN      = 'unit_assign';
    const TYPE_UNIT_UNASSIGN    = 'unit_unassign';

    // Permission assignments
    const TYPE_PERMISSION_ASSIGN   = 'permission_assign';
    const TYPE_PERMISSION_UNASSIGN = 'permission_unassign';
    const TYPE_PERMISSION_SYNC     = 'permission_sync';

    // Role CRUD
    const TYPE_ROLE_CREATE      = 'role_create';
    const TYPE_ROLE_UPDATE      = 'role_update';
    const TYPE_ROLE_DELETE      = 'role_delete';

    // Role assignments
    const TYPE_ROLE_ASSIGN      = 'role_assign';
    const TYPE_ROLE_UNASSIGN    = 'role_unassign';

    // Permission CRUD
    const TYPE_PERMISSION_CREATE = 'permission_create';
    const TYPE_PERMISSION_UPDATE = 'permission_update';
    const TYPE_PERMISSION_DELETE = 'permission_delete';

    // App Module CRUD
    const TYPE_APP_MODULE_CREATE = 'app_module_create';
    const TYPE_APP_MODULE_UPDATE = 'app_module_update';
    const TYPE_APP_MODULE_DELETE = 'app_module_delete';

    // SSO client credentials
    const TYPE_SSO_SECRET_RESET  = 'sso_secret_reset';

    // User CRUD
    const TYPE_USER_CREATE       = 'user_create';
    const TYPE_USER_UPDATE       = 'user_update';
    const TYPE_USER_DELETE       = 'user_delete';
    const TYPE_USER_TOGGLE       = 'user_toggle_active';

    public function log(
        string $type,
        string $description,
        ?string $userId = null,
        ?string $actorId = null,
        array $metadata = []
    ): void {
        UserActivityLog::create([
            'user_id'     => $userId,
            'actor_id'    => $actorId,
            'type'        => $type,
            'description' => $description,
            'metadata'    => $metadata ?: null,
        ]);
    }

    public function logForCurrentUser(string $type, string $description, array $metadata = []): void
    {
        try {
            $user = JWTAuth::user();
            $this->log($type, $description, $user?->user_id, $user?->user_id, $metadata);
        } catch (\Exception) {
            // silent fail
        }
    }

    public function getByUser(string $userId, array $filters = [])
    {
        $query = UserActivityLog::where('user_id', $userId)
            ->orderByDesc('created_at');

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function getAllLogs(array $filters = [])
    {
        $query = UserActivityLog::with(['actor'])
            ->orderByDesc('created_at');

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Filter beberapa tipe sekaligus, dipakai filter aksi CRUD di UI.
        // Harus di sisi query agar pagination tetap benar.
        if (!empty($filters['types'])) {
            $types = is_array($filters['types'])
                ? $filters['types']
                : explode(',', $filters['types']);
            $query->whereIn('type', array_filter($types));
        }

        if (!empty($filters['exclude_types'])) {
            $exclude = is_array($filters['exclude_types'])
                ? $filters['exclude_types']
                : explode(',', $filters['exclude_types']);
            $query->whereNotIn('type', $exclude);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $query->where('description', 'like', "%{$filters['search']}%");
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Ringkasan untuk kartu statistik dan grafik tren di halaman log.
     * Menghitung di sisi database supaya tidak menarik seluruh baris.
     */
    public function getStats(int $trendDays = 7): array
    {
        $createTypes = ['user_create', 'unit_create', 'role_create', 'permission_create', 'app_module_create'];
        $updateTypes = ['user_update', 'user_toggle_active', 'unit_update', 'role_update', 'permission_update',
                        'app_module_update', 'update_profile', 'change_password', 'reset_password',
                        'unit_assign', 'unit_unassign', 'role_assign', 'role_unassign',
                        'permission_assign', 'permission_unassign', 'permission_sync', 'sso_secret_reset'];
        $deleteTypes = ['user_delete', 'unit_delete', 'role_delete', 'permission_delete', 'app_module_delete'];

        $crudTypes = array_merge($createTypes, $updateTypes, $deleteTypes);

        $trend = [];
        for ($i = $trendDays - 1; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $trend[] = [
                'date'  => $day->format('Y-m-d'),
                'label' => $day->isoFormat('ddd'),
                'total' => UserActivityLog::whereDate('created_at', $day->toDateString())
                    ->whereIn('type', $crudTypes)->count(),
            ];
        }

        return [
            'total'        => UserActivityLog::count(),
            'total_crud'   => UserActivityLog::whereIn('type', $crudTypes)->count(),
            'today'        => UserActivityLog::whereDate('created_at', now()->toDateString())->count(),
            'create'       => UserActivityLog::whereIn('type', $createTypes)->count(),
            'update'       => UserActivityLog::whereIn('type', $updateTypes)->count(),
            'delete'       => UserActivityLog::whereIn('type', $deleteTypes)->count(),
            'trend'        => $trend,
            'top_actors'   => UserActivityLog::selectRaw('actor_id, count(*) as total')
                ->whereNotNull('actor_id')
                ->whereIn('type', $crudTypes)
                ->groupBy('actor_id')
                ->orderByDesc('total')
                ->limit(5)
                ->with('actor')
                ->get()
                ->map(fn ($r) => [
                    'actor_id' => $r->actor_id,
                    'email'    => $r->actor?->email ?? 'Tidak diketahui',
                    'total'    => (int) $r->total,
                ]),
        ];
    }

    // untuk menghapus log yg lebih dari 6 bulan
    public function purgeOldLogs(int $days = 180): int
    {
        return UserActivityLog::where('created_at', '<', now()->subDays($days))->delete();
    }
}
