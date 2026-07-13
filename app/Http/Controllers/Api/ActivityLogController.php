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
}
