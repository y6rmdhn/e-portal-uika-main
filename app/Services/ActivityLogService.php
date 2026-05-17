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

    public function log(
        string $type,
        string $description,
        ?int $userId = null,
        ?int $actorId = null,
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
            $this->log($type, $description, $user?->id, $user?->id, $metadata);
        } catch (\Exception) {
            // silent fail — jangan sampai gagal log break request utama
        }
    }

    public function getByUser(int $userId, array $filters = [])
    {
        $query = UserActivityLog::with(['actor:id,name,public_id'])
            ->where('user_id', $userId)
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

    // untuk menghapus log yg lebih dari 6 bulan
    public function purgeOldLogs(int $days = 180): int
    {
        return UserActivityLog::where('created_at', '<', now()->subDays($days))->delete();
    }
}
