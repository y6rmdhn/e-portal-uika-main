<?php

namespace App\Repositories\Interfaces;

use Illuminate\Support\Collection;

interface UserStatisticsRepositoryInterface
{
    public function getTotalUsers(): int;

    public function getActiveUsersCount(int $days = 30): int;

    public function getInactiveUsersCount(int $days = 30): int;

    public function getNewUsersThisMonth(): int;

    public function getActiveSessions(): int;

    public function getActiveUsersOverTime(string $period = 'weekly'): Collection;

    public function getUserGrowth(string $period = 'monthly'): Collection;

    public function getRecentActivity(int $limit = 10): Collection;

    public function getIdleUsers(int $days = 30, int $limit = 10): Collection;

    public function getUsersByRole(): Collection;

    public function getLoginHeatmap(): Collection;
}
