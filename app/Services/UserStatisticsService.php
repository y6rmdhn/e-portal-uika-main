<?php

namespace App\Services;

use App\Repositories\UserStatisticsRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class UserStatisticsService
{
    public function __construct(protected UserStatisticsRepository $repository) {}


    /**
     * Summary cards untuk bagian atas dashboard.
     * Di-cache 5 menit supaya tidak berat di DB setiap request.
     */
    public function getDashboardStats(): array
    {
        return Cache::remember('dashboard.stats', now()->addMinutes(5), function () {
            $total = $this->repository->getTotalUsers();
            $active = $this->repository->getActiveUsersCount(30);
            $inactive = $this->repository->getInactiveUsersCount(30);

            return [
                'total_users' => $total,
                'active_users' => $active,
                'inactive_users' => $inactive,
                'new_this_month' => $this->repository->getNewUsersThisMonth(),
                'active_sessions' => $this->repository->getActiveSessions(),
                'active_rate' => $total > 0 ? round(($active / $total) * 100, 1) : 0,
            ];
        });
    }

    /**
     * Data untuk grafik bar chart login harian / bulanan.
     * $period: 'weekly' | 'monthly'
     */
    public function getActiveUsersChart(string $period = 'weekly'): Collection
    {
        $cacheKey = "dashboard.active_users_chart.{$period}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($period) {
            return $this->repository->getActiveUsersOverTime($period);
        });
    }

    /**
     * Data untuk grafik line chart aktif vs tidak aktif.
     * $period: 'weekly' | 'monthly'
     */
    public function getUserGrowth(string $period = 'monthly'): Collection
    {
        $cacheKey = "dashboard.user_growth.{$period}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($period) {
            return $this->repository->getUserGrowth($period);
        });
    }

    /**
     * Log aktivitas login terbaru.
     */
    public function getRecentActivity(int $limit = 10): Collection
    {
        return Cache::remember('dashboard.recent_activity', now()->addMinutes(1), function () use ($limit) {
            return $this->repository->getRecentActivity($limit);
        });
    }

    /**
     * Daftar user yang sudah lama tidak login.
     */
    public function getIdleUsers(int $days = 30, int $limit = 10): Collection
    {
        $cacheKey = "dashboard.idle_users.{$days}";

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($days, $limit) {
            return $this->repository->getIdleUsers($days, $limit);
        });
    }

    /**
     * Distribusi user per role (untuk donut chart).
     */
    public function getUsersByRole(): Collection
    {
        return Cache::remember('dashboard.users_by_role', now()->addMinutes(10), function () {
            return $this->repository->getUsersByRole();
        });
    }

    /**
     * Heatmap login per jam (fitur tambahan).
     */
    public function getLoginHeatmap(): Collection
    {
        return Cache::remember('dashboard.login_heatmap', now()->addMinutes(30), function () {
            return $this->repository->getLoginHeatmap();
        });
    }

    /**
     * Flush semua cache dashboard (dipanggil setelah ada perubahan data user).
     */
    public function clearCache(): void
    {
        $keys = [
            'dashboard.stats',
            'dashboard.active_users_chart.weekly',
            'dashboard.active_users_chart.monthly',
            'dashboard.user_growth.weekly',
            'dashboard.user_growth.monthly',
            'dashboard.recent_activity',
            'dashboard.idle_users.30',
            'dashboard.idle_users.60',
            'dashboard.idle_users.90',
            'dashboard.users_by_role',
            'dashboard.login_heatmap',
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }
}
