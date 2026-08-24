<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LoginLogResource;
use App\Services\LoginLogService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function stats(): JsonResponse
    {
        try {
            $total   = $this->loginLogService->getAllLogs(['per_page' => 1])->total();
            $success = $this->loginLogService->getAllLogs(['per_page' => 1, 'status' => 'success'])->total();
            $failed  = $this->loginLogService->getAllLogs(['per_page' => 1, 'status' => 'failed'])->total();

            return $this->successResponse([
                'total'   => $total,
                'success' => $success,
                'failed'  => $failed,
            ], 'Login log stats retrieved');
        } catch (\Throwable $e) {
            return $this->errorResponse('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function grouped(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['date_from', 'date_to', 'status', 'user_id', 'device_type', 'search']);

            $query = DB::table('user_login_logs')
                ->selectRaw("
                DATE(created_at) as date,
                user_id,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                MAX(ip_address) as last_ip,
                MAX(browser) as browser,
                MAX(platform) as platform,
                MAX(device_type) as device_type,
                MAX(created_at) as last_login_at
            ")
                ->groupBy('date', 'user_id')
                ->orderByDesc('date')
                ->orderByDesc('last_login_at');

            if (!empty($filters['date_from'])) {
                $query->whereDate('created_at', '>=', $filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
                $query->whereDate('created_at', '<=', $filters['date_to']);
            }
            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            if (!empty($filters['user_id'])) {
                $query->where('user_id', $filters['user_id']);
            }

            // Filter device sebelumnya dikirim frontend tapi tidak pernah dibaca,
            // sehingga dropdown-nya tidak berefek apa pun.
            if (!empty($filters['device_type'])) {
                $query->where('device_type', $filters['device_type']);
            }

            // Cari berdasarkan email atau IP. Email ada di tabel user, jadi
            // diterjemahkan dulu menjadi daftar user_id.
            if (!empty($filters['search'])) {
                $term = $filters['search'];
                $matchedIds = \App\Models\User::where('email', 'like', "%{$term}%")
                    ->pluck('user_id')
                    ->all();

                $query->where(function ($q) use ($matchedIds, $term) {
                    $q->where('ip_address', 'like', "%{$term}%");
                    if (!empty($matchedIds)) {
                        $q->orWhereIn('user_id', $matchedIds);
                    }
                });
            }

            $logs = $query->paginate($request->query('per_page', 20));

            // Inject user data dari UCL
            $userIds = collect($logs->items())->pluck('user_id')
                ->filter()
                ->unique()
                ->filter(fn($id) => preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id));

            $users = \App\Models\User::whereIn('user_id', $userIds)
                ->get(['user_id', 'email', 'role'])
                ->keyBy('user_id');

            $items = collect($logs->items())->map(function ($log) use ($users) {
                $user = $users->get($log->user_id);
                return [
                    'date'        => $log->date,
                    'user_id'     => $log->user_id,
                    'email'       => $user?->email ?? '-',
                    'role'        => $user?->role ?? '-',
                    'total'       => $log->total,
                    'success'     => $log->success,
                    'failed'      => $log->failed,
                    'last_ip'     => $log->last_ip,
                    'browser'     => $log->browser,
                    'platform'    => $log->platform,
                    'device_type' => $log->device_type,
                    'last_login_at' => $log->last_login_at,
                ];
            });

            return response()->json([
                'status'  => true,
                'message' => 'Grouped login logs retrieved successfully',
                'data'    => $items,
                'meta'    => [
                    'current_page' => $logs->currentPage(),
                    'per_page'     => $logs->perPage(),
                    'total'        => $logs->total(),
                    'last_page'    => $logs->lastPage(),
                    'from'         => $logs->firstItem(),
                    'to'           => $logs->lastItem(),
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->errorResponse('Failed: ' . $e->getMessage(), 500);
        }
    }
}
