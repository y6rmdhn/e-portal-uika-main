<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Role;
use App\Http\Helper\ResponseBuilder;
use App\Services\ActivityLogService;
use Tymon\JWTAuth\Facades\JWTAuth;

class RoleController extends Controller
{
    protected ActivityLogService $activityLog;

    public function __construct(ActivityLogService $activityLog)
    {
        $this->activityLog = $activityLog;
    }
    /**
     * GET /api/admins/roles
     * List all roles with their permissions.
     */
    public function index()
    {
        $roles = Role::with('permissions')->get();

        return ResponseBuilder::success(200, 'success', $roles);
    }

    /**
     * GET /api/admins/roles/{id}
     * Show a single role.
     */
    public function show($id)
    {
        $role = Role::with('permissions')->find($id);

        if (!$role) {
            return response()->json([
                'status'  => 404,
                'message' => 'Role not found.',
                'data'    => [],
            ], 404);
        }

        return ResponseBuilder::success(200, 'success', $role);
    }

    /**
     * POST /api/admins/roles
     * Create a new role.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'       => 'required|string|max:100|unique:m_jabatan,name',
            'guard_name' => 'sometimes|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => $validator->errors()->first(),
                'data'    => [],
            ], 422);
        }

        $role = Role::create([
            'name'       => $request->name,
            'guard_name' => $request->input('guard_name', 'web') ? $request->input('guard_name', 'web') : "web",
        ]);

        // Log Aktivitas
        try {
            $actor = JWTAuth::user();
            $this->activityLog->log(
                'role_create',
                "Membuat role baru: {$role->name}",
                $actor?->user_id,
                $actor?->user_id,
                ['role_id' => $role->id, 'name' => $role->name]
            );
        } catch (\Exception $e) {
            // silent fail
        }

        return ResponseBuilder::success(201, 'Role created successfully.', $role);
    }

    /**
     * PUT /api/admins/roles/{id}
     * Update an existing role.
     */
    public function update(Request $request, $id)
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json([
                'status'  => 404,
                'message' => 'Role not found.',
                'data'    => [],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'       => 'sometimes|required|string|max:100|unique:m_jabatan,name,' . $id,
            'guard_name' => 'sometimes|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => $validator->errors()->first(),
                'data'    => [],
            ], 422);
        }

        $oldName = $role->name;
        $role->update($request->only('name', 'guard_name'));

        // Log Aktivitas
        try {
            $actor = JWTAuth::user();
            $this->activityLog->log(
                'role_update',
                "Memperbarui role: {$oldName} → {$role->name}",
                $actor?->user_id,
                $actor?->user_id,
                ['role_id' => $role->id, 'before' => $oldName, 'after' => $role->name]
            );
        } catch (\Exception $e) {
            // silent fail
        }

        return ResponseBuilder::success(200, 'Role updated successfully.', $role);
    }

    /**
     * DELETE /api/admins/roles/{id}
     * Delete a role.
     */
    public function destroy($id)
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json([
                'status'  => 404,
                'message' => 'Role not found.',
                'data'    => [],
            ], 404);
        }

        $roleName = $role->name;
        $role->delete();

        // Log Aktivitas
        try {
            $actor = JWTAuth::user();
            $this->activityLog->log(
                'role_delete',
                "Menghapus role: {$roleName}",
                $actor?->user_id,
                $actor?->user_id,
                ['role_id' => $id, 'name' => $roleName]
            );
        } catch (\Exception $e) {
            // silent fail
        }

        return response()->json([
            'status'  => 200,
            'message' => 'Role deleted successfully.',
            'data'    => [],
        ], 200);
    }

    /**
     * POST /api/admins/roles/assign
     * Assign a role to one or multiple users (Bulk assignment).
     */
    public function assignRole(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'role_id'    => 'required|integer|exists:m_jabatan,id',
            'user_ids'   => 'required_without:user_id|array',
            'user_ids.*' => 'string|exists:App\Models\User,user_id',
            'user_id'    => 'required_without:user_ids|string|exists:App\Models\User,user_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => $validator->errors()->first(),
                'data'    => [],
            ], 422);
        }

        $role = Role::findOrFail($request->role_id);
        
        $userIds = $request->has('user_ids') ? $request->user_ids : [$request->user_id];
        
        $users = \App\Models\User::whereIn('user_id', $userIds)->get();

        foreach ($users as $user) {
            $user->assignRole($role->name);
        }

        // Log Aktivitas
        try {
            $userEmails = $users->pluck('email')->toArray();
            $emailList = implode(', ', $userEmails);
            $actor = JWTAuth::user();
            $this->activityLog->log(
                'role_assign',
                "Menugaskan role {$role->name} ke user: ({$emailList})",
                $actor?->user_id,
                $actor?->user_id,
                ['role_id' => $role->id, 'role_name' => $role->name, 'user_emails' => $userEmails]
            );
        } catch (\Exception $e) {
            // silent fail
        }

        return ResponseBuilder::success(200, 'Role assigned successfully to ' . $users->count() . ' user(s).');
    }

    /**
     * POST /api/admins/roles/unassign
     * Unassign a role from one or multiple users (Bulk unassignment).
     */
    public function unassignRole(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'role_id'    => 'required|integer|exists:m_jabatan,id',
            'user_ids'   => 'required_without:user_id|array',
            'user_ids.*' => 'string|exists:App\Models\User,user_id',
            'user_id'    => 'required_without:user_ids|string|exists:App\Models\User,user_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => $validator->errors()->first(),
                'data'    => [],
            ], 422);
        }

        $role = Role::findOrFail($request->role_id);
        
        $userIds = $request->has('user_ids') ? $request->user_ids : [$request->user_id];
        
        $users = \App\Models\User::whereIn('user_id', $userIds)->get();

        foreach ($users as $user) {
            $user->removeRole($role->name);
        }

        // Log Aktivitas
        try {
            $userEmails = $users->pluck('email')->toArray();
            $emailList = implode(', ', $userEmails);
            $actor = JWTAuth::user();
            $this->activityLog->log(
                'role_unassign',
                "Mencabut role {$role->name} dari user: ({$emailList})",
                $actor?->user_id,
                $actor?->user_id,
                ['role_id' => $role->id, 'role_name' => $role->name, 'user_emails' => $userEmails]
            );
        } catch (\Exception $e) {
            // silent fail
        }

        return ResponseBuilder::success(200, 'Role unassigned successfully from ' . $users->count() . ' user(s).');
    }
}
