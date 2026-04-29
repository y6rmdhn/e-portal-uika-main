<?php

namespace App\Repositories\Interfaces;

use App\Models\LoginLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface LoginLogRepositoryInterface
{
    public function create(array $data): LoginLog;

    public function getLogsByUser(string $userId, array $filters = []): LengthAwarePaginator;

    public function getAllLogs(array $filters = []): LengthAwarePaginator;

    public function countRecentFailedAttempts(string $identifier, int $minutes = 15): int;

    public function getFailedAttemptsByIp(string $ip, int $minutes = 15): int;

    public function deleteOldLogs(int $days = 90): int;

    public function getSuspiciousIps(int $threshold = 10, int $minutes = 60): Collection;
}
