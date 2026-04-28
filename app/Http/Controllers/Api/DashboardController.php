<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActiveUsersChartResource;
use App\Http\Resources\IdleUsersResource;
use App\Http\Resources\RecentActivityResource;
use App\Http\Resources\RoleDistributionResource;
use App\Http\Resources\StatsResource;
use App\Http\Resources\UserGrowthResource;
use App\Services\UserStatisticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected UserStatisticsService $statisticsService
    ) {}

    /**
     * GET /api/admin/dashboard/stats
     * Summary cards: total user, aktif, tidak aktif, sesi SSO, dll.
     */
    public function stats(): JsonResponse
    {
        $data = $this->statisticsService->getDashboardStats();

        return response()->json([
            'success' => true,
            'data'    => StatsResource::make((object) $data),
        ]);
    }

    /**
     * GET /api/admin/dashboard/active-users?period=weekly
     * Data bar chart login harian / bulanan.
     */
    public function activeUsersChart(Request $request): JsonResponse
    {
        $period = $request->query('period', 'weekly');

        if (! in_array($period, ['weekly', 'monthly'])) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter period harus weekly atau monthly.',
            ], 422);
        }

        $data = $this->statisticsService->getActiveUsersChart($period);

        return response()->json([
            'success' => true,
            'period'  => $period,
            'data'    => ActiveUsersChartResource::collection($data),
        ]);
    }

    /**
     * GET /api/admin/dashboard/user-growth?period=monthly
     * Data line chart aktif vs tidak aktif.
     */
    public function userGrowth(Request $request): JsonResponse
    {
        $period = $request->query('period', 'monthly');

        if (! in_array($period, ['weekly', 'monthly'])) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter period harus weekly atau monthly.',
            ], 422);
        }

        $data = $this->statisticsService->getUserGrowth($period);

        return response()->json([
            'success' => true,
            'period'  => $period,
            'data'    => UserGrowthResource::collection($data),
        ]);
    }

    /**
     * GET /api/admin/dashboard/recent-activity?limit=10
     * Log 10 login terbaru.
     */
    public function recentActivity(Request $request): JsonResponse
    {
        $limit = (int) $request->query('limit', 10);
        $limit = min(max($limit, 1), 50); // clamp 1–50

        $data = $this->statisticsService->getRecentActivity($limit);

        return response()->json([
            'success' => true,
            'data'    => RecentActivityResource::collection($data),
        ]);
    }

    /**
     * GET /api/admin/dashboard/idle-users?days=30&limit=10
     * Daftar user yang sudah lama tidak login.
     */
    public function idleUsers(Request $request): JsonResponse
    {
        $days  = (int) $request->query('days', 30);
        $limit = (int) $request->query('limit', 10);

        $days  = max($days, 1);
        $limit = min(max($limit, 1), 100);

        $data = $this->statisticsService->getIdleUsers($days, $limit);

        return response()->json([
            'success'    => true,
            'idle_after' => "{$days} hari",
            'data'       => IdleUsersResource::collection($data),
        ]);
    }

    /**
     * GET /api/admin/dashboard/role-distribution
     * Distribusi user per role (donut chart).
     */
    public function roleDistribution(): JsonResponse
    {
        $data = $this->statisticsService->getUsersByRole();

        return response()->json([
            'success' => true,
            'data'    => RoleDistributionResource::collection($data),
        ]);
    }

    /**
     * GET /api/admin/dashboard/login-heatmap
     * Heatmap login per jam dalam seminggu.
     */
    public function loginHeatmap(): JsonResponse
    {
        $data = $this->statisticsService->getLoginHeatmap();

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * POST /api/admin/dashboard/clear-cache
     * Flush semua cache dashboard (hanya super admin).
     */
    public function clearCache(): JsonResponse
    {
        $this->statisticsService->clearCache();

        return response()->json([
            'success' => true,
            'message' => 'Cache dashboard berhasil di-flush.',
        ]);
    }
}
