<?php

namespace App\Repositories;

use App\Models\LoginLog;
use App\Repositories\Interfaces\LoginLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class LoginLogRepository implements LoginLogRepositoryInterface
{
    public function __construct(protected LoginLog $model) {}

    public function create(array $data): LoginLog
    {
        return $this->model->create($data);
    }

    /**
     * Log aktivitas login milik satu user (untuk halaman detail user).
     */
    public function getLogsByUser(string $userId, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->where('user_id', $userId)->orderByDesc('created_at');


        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Semua log untuk halaman monitoring admin.
     */
    public function getAllLogs(array $filters = []): LengthAwarePaginator
{
    $query = $this->model->orderByDesc('created_at');

    if (!empty($filters['status'])) {
        $query->where('status', $filters['status']);
    }

    if (!empty($filters['user_id'])) {
        $query->where('user_id', $filters['user_id']);
    }

    if (!empty($filters['ip_address'])) {
        $query->where('ip_address', 'like', "%{$filters['ip_address']}%");
    }

    if (!empty($filters['date_from'])) {
        $query->whereDate('created_at', '>=', $filters['date_from']);
    }

    if (!empty($filters['date_to'])) {
        $query->whereDate('created_at', '<=', $filters['date_to']);
    }

    if (!empty($filters['device_type'])) {
        $query->where('device_type', $filters['device_type']);
    }

    $logs = $query->paginate($filters['per_page'] ?? 20);

    // Ambil data user dari UCL berdasarkan user_id
    $userIds = $logs->pluck('user_id')->filter()->unique()->values();
    $users = \App\Models\User::whereIn('user_id', $userIds)
        ->get(['user_id', 'email', 'role'])
        ->keyBy('user_id');

    // Inject user data ke tiap log
    $logs->getCollection()->transform(function ($log) use ($users) {
        $user = $users->get($log->user_id);
        $log->user = $user ? [
            'email' => $user->email,
            'role'  => $user->role,
        ] : null;
        return $log;
    });

    return $logs;
}

    /**
     * Hitung percobaan login gagal dari email/IP dalam X menit terakhir.
     * Dipakai untuk logika rate limiting manual.
     */
    public function countRecentFailedAttempts(string $identifier, int $minutes = 15): int
    {
        return $this->model
            ->where('status', 'failed')
            ->where(function ($q) use ($identifier) {
                // identifier bisa berupa email (user_id) atau IP
                $q->where('ip_address', $identifier)->orWhere('user_id', $identifier);
            })
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->count();
    }

    public function getFailedAttemptsByIp(string $ip, int $minutes = 15): int
    {
        return $this->model
            ->where('status', 'failed')
            ->where('ip_address', $ip)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->count();
    }

    /**
     * Hapus log lama (untuk scheduled cleanup).
     * Mengembalikan jumlah row yang dihapus.
     */
    public function deleteOldLogs(int $days = 90): int
    {
        return $this->model
            ->where('created_at', '<', now()->subDays($days))
            ->delete();
    }

    /**
     * IP yang punya percobaan gagal melebihi threshold → indikasi brute force.
     */
    public function getSuspiciousIps(int $threshold = 10, int $minutes = 60): Collection
    {
        return $this->model
            ->selectRaw('ip_address, COUNT(*) as attempt_count, MAX(created_at) as last_attempt')
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->groupBy('ip_address')
            ->havingRaw('COUNT(*) >= ?', [$threshold])
            ->orderByDesc('attempt_count')
            ->get();
    }
}
