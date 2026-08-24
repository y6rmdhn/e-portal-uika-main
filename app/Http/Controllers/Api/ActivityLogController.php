<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Services\ActivityLogService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    /**
     * GET /api/admins/activity-logs
     * Semua activity log (manipulasi data, login, dsb).
     * Filter: type, date_from, date_to, search, per_page
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'type',
                'types',
                'date_from',
                'date_to',
                'search',
                'per_page',
                'exclude_types',
            ]);

            $logs = $this->activityLogService->getAllLogs($filters);

            return $this->paginatedResponse($logs, 'Activity logs retrieved successfully', ActivityLogResource::class);
        } catch (\Throwable $e) {
            return $this->errorResponse('Failed to retrieve activity logs: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/admins/activity-logs/types
     * Daftar tipe aktivitas untuk mengisi dropdown filter.
     * Sebelumnya daftar ini di-hardcode di frontend sehingga tipe baru
     * tidak pernah muncul di filter.
     */
    public function types(): JsonResponse
    {
        return $this->successResponse(
            ActivityLogResource::typeOptions(),
            'Activity log types retrieved successfully'
        );
    }

    /**
     * GET /api/admins/activity-logs/stats
     * Ringkasan untuk kartu statistik dan grafik tren.
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $days = (int) $request->query('days', 7);
            $days = max(1, min($days, 30));

            return $this->successResponse(
                $this->activityLogService->getStats($days),
                'Activity log stats retrieved successfully'
            );
        } catch (\Throwable $e) {
            return $this->errorResponse('Failed to retrieve stats: ' . $e->getMessage(), 500);
        }
    }
}
