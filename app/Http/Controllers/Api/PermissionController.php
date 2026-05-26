<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Permission;
use App\Models\AppModule;
use App\Http\Helper\ResponseBuilder;

class PermissionController extends Controller
{
    /**
     * GET /api/admins/permissions
     * List all permissions with their roles.
     */
    public function index(Request $request)
    {
        $query = Permission::with(['roles', 'appModule']);

        // Filter by appModule_id jika dikirim
        if ($request->filled('appModule_id')) {
            $query->where('appModule_id', $request->appModule_id);
        }

        $permissions = $query->get();

        return ResponseBuilder::success(200, 'success', $permissions);
    }

    /**
     * GET /api/admins/permissions/{id}
     * Show a single permission.
     */
    public function show($id)
    {
        $permission = Permission::with(['roles', 'appModule'])->find($id);

        if (!$permission) {
            return response()->json([
                'status'  => 404,
                'message' => 'Permission not found.',
                'data'    => [],
            ], 404);
        }

        return ResponseBuilder::success(200, 'success', $permission);
    }

    /**
     * POST /api/admins/permissions
     * Create a new permission.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'          => 'required|string|max:100|unique:permissions,name',
            'guard_name'    => 'sometimes|string|max:100',
            'appModule_id'  => 'required|integer|exists:app_module,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => $validator->errors()->first(),
                'data'    => [],
            ], 422);
        }

        $permission = Permission::create([
            'name'         => $request->name,
            'guard_name'   => $request->input('guard_name', 'web'),
            'appModule_id' => $request->appModule_id,
        ]);

        $permission->load('appModule');

        return ResponseBuilder::success(201, 'Permission created successfully.', $permission);
    }

    /**
     * PUT /api/admins/permissions/{id}
     * Update an existing permission.
     */
    public function update(Request $request, $id)
    {
        $permission = Permission::find($id);

        if (!$permission) {
            return response()->json([
                'status'  => 404,
                'message' => 'Permission not found.',
                'data'    => [],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'         => 'sometimes|required|string|max:100|unique:permissions,name,' . $id,
            'guard_name'   => 'sometimes|string|max:100',
            'appModule_id' => 'sometimes|required|integer|exists:app_module,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => $validator->errors()->first(),
                'data'    => [],
            ], 422);
        }

        $permission->update($request->only('name', 'guard_name', 'appModule_id'));

        $permission->load('appModule');

        return ResponseBuilder::success(200, 'Permission updated successfully.', $permission);
    }

    /**
     * POST /api/admins/permissions/bulk
     * Bulk-create multiple permissions at once.
     * Payload: { appModule_id: int, permissions: [{ name: string, guard_name?: string }] }
     * atau format singkat: { appModule_id: int, prefix: string, actions: ["view","create",...] }
     */
    public function bulkStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'appModule_id'          => 'required|integer|exists:app_module,id',
            'permissions'           => 'required|array|min:1',
            'permissions.*.name'    => 'required|string|max:100',
            'permissions.*.guard_name' => 'sometimes|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => $validator->errors()->first(),
                'data'    => [],
            ], 422);
        }

        $appModuleId = $request->appModule_id;
        $created     = [];
        $skipped     = [];

        foreach ($request->permissions as $item) {
            $name      = $item['name'];
            $guardName = $item['guard_name'] ?? 'web';

            // Skip jika nama sudah ada (unique per name+guard_name)
            $exists = Permission::where('name', $name)
                ->where('guard_name', $guardName)
                ->exists();

            if ($exists) {
                $skipped[] = $name;
                continue;
            }

            $created[] = Permission::create([
                'name'         => $name,
                'guard_name'   => $guardName,
                'appModule_id' => $appModuleId,
            ]);
        }

        return ResponseBuilder::success(201, 'Bulk permission created.', [
            'created' => $created,
            'skipped' => $skipped,
        ]);
    }

    /**
     * PUT /api/admins/permissions/bulk
     * Bulk-update multiple permissions at once.
     * Payload: { permissions: [{ id: int, name: string, guard_name?: string, appModule_id?: int }] }
     */
    public function bulkUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'permissions'              => 'required|array|min:1',
            'permissions.*.id'         => 'required|integer|exists:permissions,id',
            'permissions.*.name'       => 'required|string|max:100',
            'permissions.*.guard_name' => 'sometimes|string|max:100',
            'permissions.*.appModule_id' => 'sometimes|integer|exists:app_module,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => $validator->errors()->first(),
                'data'    => [],
            ], 422);
        }

        $updated = [];
        $errors  = [];

        foreach ($request->permissions as $item) {
            $permission = Permission::find($item['id']);
            if (!$permission) {
                $errors[] = "ID {$item['id']} not found.";
                continue;
            }

            // Cek uniqueness: name+guard_name tidak boleh tabrakan dengan permission lain
            $guardName = $item['guard_name'] ?? $permission->guard_name;
            $duplicate = Permission::where('name', $item['name'])
                ->where('guard_name', $guardName)
                ->where('id', '!=', $item['id'])
                ->exists();

            if ($duplicate) {
                $errors[] = "Permission name '{$item['name']}' sudah ada.";
                continue;
            }

            $permission->update([
                'name'         => $item['name'],
                'guard_name'   => $guardName,
                'appModule_id' => $item['appModule_id'] ?? $permission->appModule_id,
            ]);

            $updated[] = $permission->fresh(['appModule']);
        }

        return ResponseBuilder::success(200, 'Bulk permission updated.', [
            'updated' => $updated,
            'errors'  => $errors,
        ]);
    }

    /**
     * DELETE /api/admins/permissions/{id}
     * Delete a single permission.
     */
    public function destroy($id)
    {
        $permission = Permission::find($id);

        if (!$permission) {
            return response()->json([
                'status'  => 404,
                'message' => 'Permission not found.',
                'data'    => [],
            ], 404);
        }

        $permission->delete();

        return response()->json([
            'status'  => 200,
            'message' => 'Permission deleted successfully.',
            'data'    => [],
        ], 200);
    }

    /**
     * DELETE /api/admins/permissions/bulk
     * Bulk-delete multiple permissions at once.
     * Payload: { ids: [1, 2, 3, ...] }
     */
    public function bulkDestroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids'   => 'required|array|min:1',
            'ids.*' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => $validator->errors()->first(),
                'data'    => [],
            ], 422);
        }

        $ids      = $request->ids;
        $existing = Permission::whereIn('id', $ids)->pluck('id')->toArray();
        $notFound = array_diff($ids, $existing);

        $deleted  = 0;
        if (!empty($existing)) {
            $deleted = Permission::whereIn('id', $existing)->delete();
        }

        return ResponseBuilder::success(200, 'Bulk permission deleted.', [
            'deleted'   => $deleted,
            'not_found' => array_values($notFound),
        ]);
    }
}
