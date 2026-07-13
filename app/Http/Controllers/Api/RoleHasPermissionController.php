<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Role;
use App\Models\Permission;
use App\Models\RoleHasPermission;
use App\Http\Helper\ResponseBuilder;
use App\Services\ActivityLogService;
use Tymon\JWTAuth\Facades\JWTAuth;

class RoleHasPermissionController extends Controller
{
    protected ActivityLogService $activityLog;

    public function __construct(ActivityLogService $activityLog)
    {
        $this->activityLog = $activityLog;
    }
    /**
     * GET /api/admins/role-permissions
     * List all role-permission assignments, optionally filtered by role_id.
     */
    public function index(Request $request)
    {
        $query = RoleHasPermission::with(['role', 'permission.appModule']);

        if ($request->filled('role_id')) {
            $query->where('role_id', $request->role_id);
        }

        if ($request->filled('permission_id')) {
            $query->where('permission_id', $request->permission_id);
        }

        $data = $query->get();

        return ResponseBuilder::success(200, 'success', $data);
    }

    /**
     * POST /api/admins/role-permissions/assign
     * Bulk assign permissions to a role.
     */
    public function assign(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'role_id'          => 'required|integer|exists:m_jabatan,id',
            'permission_ids'   => 'required|array|min:1',
            'permission_ids.*' => 'integer|exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => $validator->errors()->first(),
                'data'    => [],
            ], 422);
        }

        $roleId        = $request->role_id;
        $permissionIds = $request->permission_ids;

        // Get already assigned permission_ids for this role to avoid duplicates
        $existing = RoleHasPermission::where('role_id', $roleId)
            ->whereIn('permission_id', $permissionIds)
            ->pluck('permission_id')
            ->toArray();

        $toInsert = collect($permissionIds)
            ->diff($existing)
            ->map(fn($pid) => ['role_id' => $roleId, 'permission_id' => $pid])
            ->values()
            ->toArray();

        if (empty($toInsert)) {
            return response()->json([
                'status'  => 200,
                'message' => 'All permissions were already assigned to this role.',
                'data'    => [],
            ], 200);
        }

        RoleHasPermission::insert($toInsert);

        // Log Aktivitas
        try {
            $role = Role::find($roleId);
            $roleName = $role ? $role->name : "ID {$roleId}";
            $permissionNames = Permission::whereIn('id', $permissionIds)->pluck('name')->toArray();
            $permissionList = implode(', ', $permissionNames);

            $actor = JWTAuth::user();
            $this->activityLog->log(
                ActivityLogService::TYPE_PERMISSION_ASSIGN,
                "Menugaskan hak akses ({$permissionList}) ke role: {$roleName}",
                $actor?->user_id,
                $actor?->user_id,
                ['role_id' => $roleId, 'role_name' => $roleName, 'permission_ids' => $permissionIds]
            );
        } catch (\Exception $e) {
            // silent fail
        }

        $assigned = RoleHasPermission::with(['role', 'permission.appModule'])
            ->where('role_id', $roleId)
            ->whereIn('permission_id', $permissionIds)
            ->get();

        return ResponseBuilder::success(200, count($toInsert) . ' permission(s) assigned successfully.', $assigned);
    }

    /**
     * POST /api/admins/role-permissions/unassign
     * Bulk unassign permissions from a role.
     */
    public function unassign(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'role_id'          => 'required|integer|exists:m_jabatan,id',
            'permission_ids'   => 'required|array|min:1',
            'permission_ids.*' => 'integer|exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => $validator->errors()->first(),
                'data'    => [],
            ], 422);
        }

        $roleId        = $request->role_id;
        $permissionIds = $request->permission_ids;

        $deleted = RoleHasPermission::where('role_id', $roleId)
            ->whereIn('permission_id', $permissionIds)
            ->delete();

        if ($deleted === 0) {
            return response()->json([
                'status'  => 200,
                'message' => 'No matching role-permission assignments found.',
                'data'    => [],
            ], 200);
        }

        // Log Aktivitas
        try {
            $role = Role::find($roleId);
            $roleName = $role ? $role->name : "ID {$roleId}";
            $permissionNames = Permission::whereIn('id', $permissionIds)->pluck('name')->toArray();
            $permissionList = implode(', ', $permissionNames);

            $actor = JWTAuth::user();
            $this->activityLog->log(
                ActivityLogService::TYPE_PERMISSION_UNASSIGN,
                "Mencabut hak akses ({$permissionList}) dari role: {$roleName}",
                $actor?->user_id,
                $actor?->user_id,
                ['role_id' => $roleId, 'role_name' => $roleName, 'permission_ids' => $permissionIds]
            );
        } catch (\Exception $e) {
            // silent fail
        }

        return response()->json([
            'status'  => 200,
            'message' => $deleted . ' permission(s) unassigned successfully.',
            'data'    => [],
        ], 200);
    }

    /**
     * POST /api/admins/role-permissions/sync
     * Sync (replace) all permissions of a role with the given list.
     */
    public function sync(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'role_id'          => 'required|integer|exists:m_jabatan,id',
            'permission_ids'   => 'required|array',
            'permission_ids.*' => 'integer|exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => $validator->errors()->first(),
                'data'    => [],
            ], 422);
        }

        $roleId        = $request->role_id;
        $permissionIds = $request->permission_ids;

        DB::transaction(function () use ($roleId, $permissionIds) {
            // Remove all existing assignments for this role
            RoleHasPermission::where('role_id', $roleId)->delete();

            // Insert the new set
            $rows = collect($permissionIds)
                ->map(fn($pid) => ['role_id' => $roleId, 'permission_id' => $pid])
                ->toArray();

            if (!empty($rows)) {
                RoleHasPermission::insert($rows);
            }
        });

        // Log Aktivitas
        try {
            $role = Role::find($roleId);
            $roleName = $role ? $role->name : "ID {$roleId}";
            $permissionNames = Permission::whereIn('id', $permissionIds)->pluck('name')->toArray();
            $permissionList = implode(', ', $permissionNames);

            $actor = JWTAuth::user();
            $this->activityLog->log(
                ActivityLogService::TYPE_PERMISSION_SYNC,
                "Sinkronisasi hak akses ({$permissionList}) untuk role: {$roleName}",
                $actor?->user_id,
                $actor?->user_id,
                ['role_id' => $roleId, 'role_name' => $roleName, 'permission_ids' => $permissionIds]
            );
        } catch (\Exception $e) {
            // silent fail
        }

        $result = RoleHasPermission::with(['role', 'permission.appModule'])
            ->where('role_id', $roleId)
            ->get();

        return ResponseBuilder::success(200, 'Permissions synced successfully.', $result);
    }
}
