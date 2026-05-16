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

    public function getTotalUsers(): int
    {
        return $this->model->count();
    }

    public function getActiveUsersCount(int $days = 30): int
    {
        return $this->model->where('last_login_at', '>=', now()->subDays($days))->count();
    }

    public function getInactiveUsersCount(int $days = 30): int
    {
        return $this->model->where(function ($q) use ($days) {
            $q->where('last_login_at', '<', now()->subDays($days))->orWhereNull('last_login_at');
        })->count();
    }

    public function getNewUsersThisMonth(): int
    {
        return $this->model->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();
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
        // 7 hari terakhir, group per hari
        if ($period === 'weekly') {
            return collect(range(6, 0))->map(function ($daysAgo) {
                $date = now()->subDays($daysAgo)->toDateString();
                $count = $this->model->whereDate('last_login_at', $date)->count();

                return [
                    'label' => now()->subDays($daysAgo)->translatedFormat('D'),
                    'date'  => $date,
                    'count' => $count,
                ];
            });
        }

        // Monthly: 6 bulan terakhir, group per bulan
        return collect(range(5, 0))->map(function ($monthsAgo) {
            $date = now()->subMonths($monthsAgo);
            $count = $this->model->whereMonth('last_login_at', $date->month)
                ->whereYear('last_login_at', $date->year)
                ->count();

            return [
                'label' => now()->subMonths($monthsAgo)->translatedFormat('M Y'),
                'date'  => $date,
                'count' => $count,
            ];
        });
    }

    public function getUserGrowth(string $period = 'monthly'): Collection
    {
        if ($period === 'weekly') {
            return collect(range(6, 0))->map(function ($daysAgo) {
                $date = now()->subDays($daysAgo)->toDateString();

                return [
                    'label' => now()->subDays($daysAgo)->translatedFormat('D'),
                    'active' => $this->model->where('last_login_at', '>=', now()->subDays($daysAgo + 30))->count(),
                    'inactive' => $this->model->where(function ($q) use ($date, $daysAgo) {
                        $q->where('last_login_at', '<', now()->subDays($daysAgo + 30))->orWhereNull('last_login_at');
                    })->whereDate('created_at', '<=', $date)->count(),
                ];
            });
        }

        return collect(range(5, 0))->map(function ($monthsAgo) {
            $date = now()->subMonths($monthsAgo);

            return [
                'label' => $date->translatedFormat('M'),
                'active' => $this->model->whereMonth('last_login_at', $date->month)->whereYear('last_login_at', $date->year)->count(),
                'inactive' => $this->model->where(function ($q) use ($date) {
                    $q->where('last_login_at', '<', $date->copy()->subDays(30))->orWhereNull('last_login_at');
                })->whereYear('created_at', '<=', $date->year)->count(),
            ];
        });
    }

    public function getRecentActivity(int $limit = 10): Collection
    {
        return $this->model->whereNotNull('last_login_at')
            ->with('roles')
            ->orderByDesc('last_login_at')
            ->limit($limit)
            ->get(['id', 'name', 'email', 'last_login_at'])
            ->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->roles->first()?->name ?? 'user',
                'last_login_at' => $u->last_login_at,
            ]);
    }

    public function getIdleUsers(int $days = 30, int $limit = 10): Collection
    {
        return $this->model->where(function ($q) use ($days) {
            $q->where('last_login_at', '<', now()->subDays($days))->orWhereNull('last_login_at');
        })
            ->with('roles')
            ->orderBy('last_login_at')
            ->limit($limit)
            ->get(['id', 'name', 'email', 'last_login_at'])
            ->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->roles->first()?->name ?? 'user',
                'last_login_at' => $u->last_login_at,
                'idle_days' => $u->last_login_at ? now()->diffInDays($u->last_login_at) : null,
            ]);
    }

    public function getUsersByRole(): Collection
    {
        return DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->select('roles.name as role', DB::raw('count(*) as total'))
            ->where('model_has_roles.model_type', get_class($this->model))
            ->groupBy('roles.name')
            ->get();
    }

    public function getLoginHeatmap(): Collection
    {
        return DB::table('user_login_log')
            ->select(
                DB::raw('DAYOFWEEK(created_at) as day_of_week'),
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('count(*) as count')
            )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('day_of_week', 'hour')
            ->get();
    }
}
