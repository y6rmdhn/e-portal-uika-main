<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Helper\ResponseBuilder;
use App\Models\AppModule;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class MyModuleController extends Controller
{
    /**
     * GET /api/my-modules
     *
     * Mengembalikan daftar App Module yang bisa diakses oleh user yang sedang login,
     * berdasarkan permission yang dimiliki rolenya.
     */
    public function index(Request $request)
    {
        $user = JWTAuth::user();

        $isGlobalAdmin = in_array(strtolower($user->role ?? ''), ['admin', 'super-admin']) || $user->hasAnyRole(['admin', 'super-admin']);

        // Admin & super-admin dapat akses ke semua modul
        if ($isGlobalAdmin) {
            $modules = AppModule::orderBy('name')->get();

            return ResponseBuilder::success(200, 'success', [
                'is_admin'   => true,
                'role'       => $user->getRoleNames()->first() ?? $user->role,
                'modules'    => $modules,
            ]);
        }

        // User biasa: ambil permission dari semua role yang dimiliki
        $permissionModuleIds = $user->getAllPermissions()
            ->pluck('appModule_id')
            ->filter()       // buang null
            ->unique()
            ->values();

        $modules = AppModule::whereIn('id', $permissionModuleIds)
            ->orderBy('name')
            ->get();

        return ResponseBuilder::success(200, 'success', [
            'is_admin'   => false,
            'role'       => $user->getRoleNames()->first() ?? $user->role,
            'modules'    => $modules,
        ]);
    }

    /**
     * GET /api/my-modules/{appModuleId}/roles
     *
     * Mengembalikan daftar role yang dimiliki user untuk modul tertentu.
     */
    public function getRolesForModule($appModuleId)
    {
        $user = JWTAuth::user();

        // Pastikan modul ada
        $module = AppModule::find($appModuleId);
        if (!$module) {
            return ResponseBuilder::success(404, 'App module not found', []);
        }

        $isGlobalAdmin = in_array(strtolower($user->role ?? ''), ['admin', 'super-admin']) || $user->hasAnyRole(['admin', 'super-admin']);

        // Admin/super-admin: kembalikan semua role yang mereka miliki
        if ($isGlobalAdmin) {
            $roles = $user->roles()
                ->select('id', 'name')
                ->get()
                ->map(fn($r) => [
                    'role_id'   => $r->id,
                    'role_name' => $r->name,
                ])
                ->values();

            return ResponseBuilder::success(200, 'success', [
                'module_id'   => (int) $appModuleId,
                'module_name' => $module->name,
                'roles'       => $roles,
            ]);
        }

        // User biasa: cari role yang user miliki DAN memiliki permission di modul ini
        $roles = $user->roles()
            ->whereHas('permissions', function ($query) use ($appModuleId) {
                $query->where('appModule_id', $appModuleId);
            })
            ->select('id', 'name')
            ->get()
            ->map(fn($r) => [
                'role_id'   => $r->id,
                'role_name' => $r->name,
            ])
            ->values();

        return ResponseBuilder::success(200, 'success', [
            'module_id'   => (int) $appModuleId,
            'module_name' => $module->name,
            'roles'       => $roles,
        ]);
    }
}
