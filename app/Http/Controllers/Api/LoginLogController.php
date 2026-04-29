<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LoginLogResource;
use App\Services\LoginLogService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoginLogController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected LoginLogService $loginLogService
    ) {}

    /**
     * GET /api/admins/security/logs
     * Semua log login (untuk halaman monitoring admin).
     * Filter: status, ip_address, user_id, date_from, date_to, device_type, per_page
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'status',
                'ip_address',
                'user_id',
                'date_from',
                'date_to',
                'device_type',
                'per_page',
            ]);

            $logs = $this->loginLogService->getAllLogs($filters);

            return $this->paginatedResponse($logs, 'Login logs retrieved successfully', LoginLogResource::class);
        } catch (\Throwable $e) {
            return $this->errorResponse('Failed to retrieve logs: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/admins/security/logs/user/{id}
     * Riwayat login milik satu user (audit per-user).
     */
    public function byUser(Request $request, string $id): JsonResponse
    {
        try {
            $filters = $request->only(['status', 'date_from', 'date_to', 'per_page']);
            $logs    = $this->loginLogService->getLogsByUser($id, $filters);

            return $this->paginatedResponse($logs, 'User login logs retrieved successfully', LoginLogResource::class);
        } catch (\Throwable $e) {
            return $this->errorResponse('Failed to retrieve user logs: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/admins/security/suspicious-ips
     * IP yang dicurigai melakukan brute force.
     */
    public function suspiciousIps(): JsonResponse
    {
        try {
            $ips = $this->loginLogService->getSuspiciousIps();

            return $this->successResponse($ips, 'Suspicious IPs retrieved successfully');
        } catch (\Throwable $e) {
            return $this->errorResponse('Failed to retrieve suspicious IPs: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/admins/security/rate-limit-status?ip=x&email=y
     * Cek status lockout IP / email tertentu.
     */
    public function rateLimitStatus(Request $request): JsonResponse
    {
        try {
            $ip    = $request->query('ip', $request->ip());
            $email = $request->query('email', '');

            $status = $this->loginLogService->getRateLimitStatus($ip, $email);

            return $this->successResponse($status, 'Rate limit status retrieved');
        } catch (\Throwable $e) {
            return $this->errorResponse('Failed to get rate limit status: ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/admins/security/logs/purge?days=90
     * Hapus log lama (super admin only).
     */
    public function purge(Request $request): JsonResponse
    {
        try {
            $days    = (int) $request->query('days', 90);
            $days    = max($days, 30); // minimum 30 hari, jangan bisa hapus yang baru
            $deleted = $this->loginLogService->purgeOldLogs($days);

            return $this->successResponse(
                ['deleted_count' => $deleted],
                "Berhasil menghapus {$deleted} log yang lebih dari {$days} hari."
            );
        } catch (\Throwable $e) {
            return $this->errorResponse('Failed to purge logs: ' . $e->getMessage(), 500);
        }
    }
}
