<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Permission;
use App\Models\AppModule;
use App\Http\Helper\ResponseBuilder;
use App\Services\ActivityLogService;
use Tymon\JWTAuth\Facades\JWTAuth;

class PermissionController extends Controller
{
    protected ActivityLogService $activityLog;

    public function __construct(ActivityLogService $activityLog)
    {
        $this->activityLog = $activityLog;
    }

    /**
     * Catat aktivitas. Kegagalan logging tidak boleh menggagalkan operasi utama,
     * jadi exception-nya sengaja ditelan.
     */
    private function logActivity(string $type, string $description, array $metadata = []): void
    {
        try {
            $actor = JWTAuth::user();
            $this->activityLog->log($type, $description, $actor?->user_id, $actor?->user_id, $metadata);
        } catch (\Exception $e) {
            // silent fail
        }
    }

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

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $query->orderBy('name');

        // ?all=1 → daftar penuh, dipakai selector (Role ↔ Permission, dialog).
        // Tanpa itu, endpoint ini selalu dipaginasi.
        if ($request->boolean('all')) {
            return ResponseBuilder::success(200, 'success', $query->get());
        }

        return ResponseBuilder::paginated($query->paginate($request->query('per_page', 25)));
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

        $this->logActivity(
            ActivityLogService::TYPE_PERMISSION_CREATE,
            "Membuat permission: {$permission->name}",
            ['permission_id' => $permission->id, 'name' => $permission->name, 'appModule_id' => $permission->appModule_id]
        );

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

        $oldName = $permission->name;

        $permission->update($request->only('name', 'guard_name', 'appModule_id'));

        $permission->load('appModule');

        $this->logActivity(
            ActivityLogService::TYPE_PERMISSION_UPDATE,
            "Memperbarui permission: {$oldName} -> {$permission->name}",
            [
                'permission_id' => $permission->id,
                'before'        => ['name' => $oldName],
                'after'         => ['name' => $permission->name, 'appModule_id' => $permission->appModule_id],
            ]
        );

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

        if (count($created) > 0) {
            $names = array_map(fn ($p) => $p->name, $created);
            $this->logActivity(
                ActivityLogService::TYPE_PERMISSION_CREATE,
                count($names) === 1
                    ? "Membuat permission: {$names[0]}"
                    : 'Membuat ' . count($names) . ' permission: ' . implode(', ', $names),
                ['appModule_id' => $appModuleId, 'created' => $names, 'skipped' => $skipped]
            );
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
        $changes = [];

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

            $oldName = $permission->name;

            $permission->update([
                'name'         => $item['name'],
                'guard_name'   => $guardName,
                'appModule_id' => $item['appModule_id'] ?? $permission->appModule_id,
            ]);

            $changes[] = ['id' => $permission->id, 'from' => $oldName, 'to' => $permission->name];
            $updated[] = $permission->fresh(['appModule']);
        }

        if (count($changes) > 0) {
            $ringkas = array_map(fn ($c) => "{$c['from']} -> {$c['to']}", $changes);
            $this->logActivity(
                ActivityLogService::TYPE_PERMISSION_UPDATE,
                count($ringkas) === 1
                    ? "Memperbarui permission: {$ringkas[0]}"
                    : 'Memperbarui ' . count($ringkas) . ' permission: ' . implode(', ', $ringkas),
                ['changes' => $changes, 'errors' => $errors]
            );
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

        $deletedName = $permission->name;

        $permission->delete();

        $this->logActivity(
            ActivityLogService::TYPE_PERMISSION_DELETE,
            "Menghapus permission: {$deletedName}",
            ['permission_id' => $id, 'name' => $deletedName]
        );

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
        $rows     = Permission::whereIn('id', $ids)->get(['id', 'name']);
        $existing = $rows->pluck('id')->toArray();
        $names    = $rows->pluck('name')->toArray();
        $notFound = array_diff($ids, $existing);

        $deleted  = 0;
        if (!empty($existing)) {
            $deleted = Permission::whereIn('id', $existing)->delete();

            $this->logActivity(
                ActivityLogService::TYPE_PERMISSION_DELETE,
                count($names) === 1
                    ? "Menghapus permission: {$names[0]}"
                    : 'Menghapus ' . count($names) . ' permission: ' . implode(', ', $names),
                ['permission_ids' => $existing, 'names' => $names]
            );
        }

        return ResponseBuilder::success(200, 'Bulk permission deleted.', [
            'deleted'   => $deleted,
            'not_found' => array_values($notFound),
        ]);
    }
}
