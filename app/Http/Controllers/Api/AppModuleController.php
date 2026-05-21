<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppModule;
use App\Http\Helper\ResponseBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

class AppModuleController extends Controller
{
    public function index()
    {
        $modules = AppModule::with('roles')->orderBy('order')->get();
        return ResponseBuilder::success(200, "success", $modules);
    }

    // Untuk user — hanya tampilkan modul aktif yang sesuai role user
    public function forUser()
    {
        $user    = auth()->user();
        $roleIds = $user->roles->pluck('id');

        $modules = AppModule::with('roles')
            ->where('is_active', true)
            ->whereHas('roles', fn($q) => $q->whereIn('roles.id', $roleIds))
            ->orderBy('order')
            ->get();

        return ResponseBuilder::success(200, "success", $modules);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255|unique:app_module,name',
            'url'         => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
            'order'       => 'integer',
            'roles'       => 'required|array',
            'roles.*'     => 'string|exists:roles,name',
            'icon'        => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('icon')) {
            $data['icon'] = $request->file('icon')->store('modules/icons', 'public');
        }

        $module = AppModule::create($data);

        // Sync roles by name
        $roleIds = Role::whereIn('name', $data['roles'])->pluck('id');
        $module->roles()->sync($roleIds);

        return ResponseBuilder::success(201, "Modul berhasil dibuat", $module->load('roles'));
    }

    public function update(Request $request, string $id)
    {
        $module = AppModule::findOrFail($id);

        $data = $request->validate([
            'name'        => 'sometimes|string|max:255|unique:app_module,name,' . $module->id,
            'url'         => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
            'order'       => 'integer',
            'roles'       => 'sometimes|array',
            'roles.*'     => 'string|exists:roles,name',
            'icon'        => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('icon')) {
            if ($module->icon) Storage::disk('public')->delete($module->icon);
            $data['icon'] = $request->file('icon')->store('modules/icons', 'public');
        }

        $module->update($data);

        if (!empty($data['roles'])) {
            $roleIds = Role::whereIn('name', $data['roles'])->pluck('id');
            $module->roles()->sync($roleIds);
        }

        return ResponseBuilder::success(200, "Modul berhasil diupdate", $module->load('roles'));
    }

    public function destroy(string $id)
    {
        $module = AppModule::findOrFail($id);
        
        if ($module->icon) {
            Storage::disk('public')->delete($module->icon);
        }
        
        $module->delete();

        return ResponseBuilder::success(200, "Modul berhasil dihapus", null);
    }
}