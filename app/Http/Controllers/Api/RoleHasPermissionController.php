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

class RoleHasPermissionController extends Controller
{
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
     *
     * Body: { "role_id": 1, "permission_ids": [1, 2, 3] }
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

        $assigned = RoleHasPermission::with(['role', 'permission.appModule'])
            ->where('role_id', $roleId)
            ->whereIn('permission_id', $permissionIds)
            ->get();

        return ResponseBuilder::success(200, count($toInsert) . ' permission(s) assigned successfully.', $assigned);
    }

    /**
     * POST /api/admins/role-permissions/unassign
     * Bulk unassign permissions from a role.
     *
     * Body: { "role_id": 1, "permission_ids": [1, 2] }
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

        return response()->json([
            'status'  => 200,
            'message' => $deleted . ' permission(s) unassigned successfully.',
            'data'    => [],
        ], 200);
    }

    /**
     * POST /api/admins/role-permissions/sync
     * Sync (replace) all permissions of a role with the given list.
     *
     * Body: { "role_id": 1, "permission_ids": [1, 3] }
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

        $result = RoleHasPermission::with(['role', 'permission.appModule'])
            ->where('role_id', $roleId)
            ->get();

        return ResponseBuilder::success(200, 'Permissions synced successfully.', $result);
    }
}
