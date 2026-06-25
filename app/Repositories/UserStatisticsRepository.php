<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Interfaces\UserStatisticsRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UserStatisticsRepository implements UserStatisticsRepositoryInterface
{
    protected User $model;

    public function __construct(User $model)
    {
        $this->model = $model;
    }

    // ── Dari tb_users UCL (PostgreSQL) ────────────────────────────────────────

    public function getTotalUsers(): int
    {
        return $this->model->count();
    }

    public function getActiveUsersCount(int $days = 30): int
    {
        // Pakai user_login_logs MySQL
        $activeIds = DB::table('user_login_logs')
            ->where('created_at', '>=', now()->subDays($days))
            ->where('status', 'success')
            ->distinct()
            ->pluck('user_id');

        return $activeIds->count();
    }

    public function getInactiveUsersCount(int $days = 30): int
    {
        $total = $this->getTotalUsers();
        $active = $this->getActiveUsersCount($days);
        return max(0, $total - $active);
    }

    public function getNewUsersThisMonth(): int
    {
        return $this->model
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
    }

    public function getTotalLoginToday(): int
    {
        return DB::table('user_login_logs')
            ->whereDate('created_at', today())
            ->where('status', 'success')
            ->count();
    }

    public function getActiveUsersOverTime(string $period = 'weekly'): Collection
    {
        if ($period === 'weekly') {
            return collect(range(6, 0))->map(function ($daysAgo) {
                $date = now()->subDays($daysAgo)->toDateString();
                $count = DB::table('user_login_logs')
                    ->whereDate('created_at', $date)
                    ->where('status', 'success')
                    ->distinct('user_id')
                    ->count('user_id');

                return [
                    'label' => now()->subDays($daysAgo)->translatedFormat('D'),
                    'date'  => $date,
                    'count' => $count,
                ];
            });
        }

        return collect(range(5, 0))->map(function ($monthsAgo) {
            $date = now()->subMonths($monthsAgo);
            $count = DB::table('user_login_logs')
                ->whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->where('status', 'success')
                ->distinct('user_id')
                ->count('user_id');

            return [
                'label' => $date->translatedFormat('M Y'),
                'date'  => $date->toDateString(),
                'count' => $count,
            ];
        });
    }

    public function getRecentActivity(int $limit = 10): Collection
    {
        $logs = DB::table('user_login_logs')
            ->where('status', 'success')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get(['user_id', 'ip_address', 'browser', 'platform', 'created_at']);

        // Ambil semua user_id dulu, trus query UCL sekali aja
        $userIds = $logs->pluck('user_id')
            ->filter()
            ->unique()
            ->filter(fn($id) => preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id))
            ->values();

        $users = $this->model
            ->whereIn('user_id', $userIds)
            ->get(['user_id', 'email', 'role', 'npm', 'nidn'])
            ->keyBy('user_id');

        return $logs->map(function ($log) use ($users) {
            $user = $users->get($log->user_id);
            return [
                'user_id'    => $log->user_id,
                'email'      => $user?->email ?? '-',
                'role'       => $user?->role ?? 'user',
                'npm'        => $user?->npm ?? null,
                'nidn'       => $user?->nidn ?? null,
                'ip_address' => $log->ip_address,
                'browser'    => $log->browser,
                'platform'   => $log->platform,
                'login_at'   => $log->created_at,
            ];
        });
    }

    public function getUserGrowth(string $period = 'monthly'): Collection
    {
        if ($period === 'weekly') {
            // Ambil semua data login 7 hari terakhir sekaligus
            $loginData = DB::table('user_login_logs')
                ->where('status', 'success')
                ->where('created_at', '>=', now()->subDays(6)->startOfDay())
                ->selectRaw('DATE(created_at) as date, COUNT(DISTINCT user_id) as active')
                ->groupBy('date')
                ->pluck('active', 'date');

            // Ambil total user sekali aja
            $totalUsers = $this->model->count();

            return collect(range(6, 0))->map(function ($daysAgo) use ($loginData, $totalUsers) {
                $date = now()->subDays($daysAgo)->toDateString();
                $active = $loginData[$date] ?? 0;

                return [
                    'label'    => now()->subDays($daysAgo)->translatedFormat('D'),
                    'active'   => $active,
                    'inactive' => max(0, $totalUsers - $active),
                ];
            });
        }

        // Monthly — ambil 6 bulan sekaligus
        $loginData = DB::table('user_login_logs')
            ->where('status', 'success')
            ->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
            ->selectRaw('EXTRACT(YEAR FROM created_at) as year, EXTRACT(MONTH FROM created_at) as month, COUNT(DISTINCT user_id) as active')
            ->groupBy('year', 'month')
            ->get()
            ->keyBy(fn($row) => (int)$row->year . '-' . (int)$row->month);

        $totalUsers = $this->model->count();

        return collect(range(5, 0))->map(function ($monthsAgo) use ($loginData, $totalUsers) {
            $date = now()->subMonths($monthsAgo);
            $key = $date->year . '-' . $date->month;
            $active = $loginData[$key]->active ?? 0;

            return [
                'label'    => $date->translatedFormat('M'),
                'active'   => $active,
                'inactive' => max(0, $totalUsers - $active),
            ];
        });
    }

    public function getIdleUsers(int $days = 30, int $limit = 10): Collection
    {
        // User yang tidak ada di login_logs dalam $days hari terakhir
        $activeUserIds = DB::table('user_login_logs')
            ->where('created_at', '>=', now()->subDays($days))
            ->where('status', 'success')
            ->distinct()
            ->pluck('user_id')
            ->toArray();

        $lastLogins = DB::table('user_login_logs')
            ->where('status', 'success')
            ->select('user_id', DB::raw('MAX(created_at) as last_login_at'))
            ->groupBy('user_id')
            ->pluck('last_login_at', 'user_id');

        return $this->model
            ->whereNotIn('user_id', $activeUserIds)
            ->limit($limit)
            ->get(['user_id', 'email', 'role', 'npm', 'nidn'])
            ->map(function ($user) use ($lastLogins, $days) {
                $lastLogin = $lastLogins[$user->user_id] ?? null;
                return [
                    'user_id'      => $user->user_id,
                    'email'        => $user->email,
                    'role'         => $user->role,
                    'npm'          => $user->npm,
                    'nidn'         => $user->nidn,
                    'last_login_at' => $lastLogin,
                    'idle_days'    => $lastLogin ? now()->diffInDays($lastLogin) : null,
                ];
            });
    }

    public function getUsersByRole(): Collection
    {
        return $this->model
            ->selectRaw('role, count(*) as total')
            ->groupBy('role')
            ->get()
            ->map(fn($item) => [
                'role'  => $item->role,
                'total' => $item->total,
            ]);
    }

    public function getLoginHeatmap(): Collection
    {
        return DB::table('user_login_logs')
            ->select(
                DB::raw('EXTRACT(DOW FROM created_at) + 1 as day_of_week'),
                DB::raw('EXTRACT(HOUR FROM created_at) as hour'),
                DB::raw('count(*) as count')
            )
            ->where('created_at', '>=', now()->subDays(30))
            ->where('status', 'success')
            ->groupBy('day_of_week', 'hour')
            ->get();
    }
}
