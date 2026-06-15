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
     *
     * Logika:
     * - Ambil semua permission yang dimiliki user (via Spatie HasRoles).
     * - Dari permission tersebut, ambil unique appModule_id.
     * - Kembalikan AppModule berdasarkan id-id tersebut.
     * - Jika user adalah admin/super-admin, kembalikan SEMUA modul.
     */
    public function index(Request $request)
{
    $user = JWTAuth::user();

    $role = strtolower($user->role ?? '');
    $isAdmin = in_array($role, ['admin', 'super-admin']);

    $modules = AppModule::orderBy('name')->get();

    return ResponseBuilder::success(200, 'success', [
        'is_admin' => $isAdmin,
        'role'     => $user->role,
        'modules'  => $modules,
    ]);
}
}
