<?php

use App\Http\Controllers\Api\LoginLogController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\AppModuleController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\RoleHasPermissionController;
use App\Http\Controllers\Api\MyModuleController;
use App\Http\Controllers\Api\SsoController;
use App\Http\Controllers\Api\JabatanController;
use App\Http\Controllers\Api\UnitController;
use App\Http\Controllers\Api\UserJabatanUnitController;
use App\Http\Controllers\Api\SsoIntegrationController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ==========================================
// SSO ROUTES
// ==========================================
Route::get('/sso/capabilities', [SsoController::class, 'capabilities']);
Route::post('/sso/introspect', [SsoController::class, 'introspect'])->middleware('sso.client');
Route::get('/sso/verify-access', [SsoController::class, 'verifyAccess'])->middleware(['jwt.verify', 'sso.client']);


// ==========================================
// SSO ROUTES — Khusus untuk Sub-Aplikasi
// ==========================================

// Public: tidak butuh token maupun client credentials
// Digunakan developer sub-aplikasi untuk memahami kontrak SSO
Route::get('/sso/capabilities', [SsoController::class, 'capabilities']);

// Protected: butuh SSO Client Credentials (X-SSO-Client-ID + X-SSO-Client-Secret)
// Endpoint utama sub-aplikasi — validasi token + dapatkan user & permissions
Route::post('/sso/introspect', [SsoController::class, 'introspect'])
    ->middleware('sso.client');

// Protected: butuh JWT token + SSO Client Credentials
// Cek cepat apakah user punya akses ke modul tertentu
Route::get('/sso/verify-access', [SsoController::class, 'verifyAccess'])
    ->middleware(['jwt.verify', 'sso.client']);

// ==========================================
// PUBLIC ROUTES
// ==========================================
Route::post('/auth/login', 'Api\AuthController@auth');



Route::post('/auth/login/tias', 'Api\AuthController@authTias');
Route::post('/register', 'Api\AuthController@register');
Route::get('/public/units', [UnitController::class, 'index'])->name('public.units');
Route::get('/public/jabatans', [JabatanController::class, 'index'])->name('public.jabatans');
Route::get('/check-id', 'Api\AuthController@checkId');
Route::post('/password/email', 'Api\AuthController@sendResetLinkEmail');
Route::post('/password/reset', 'Api\AuthController@resetPassword');
Route::get('/auth/google/redirect', 'Api\AuthController@redirectToGoogle');
Route::get('/auth/google/callback', 'Api\AuthController@handleGoogleCallback');
Route::get('/auth/token-from-cookie', 'Api\AuthController@tokenFromCookie');
Route::get('/validate/nidn', 'Api\AuthController@validateNidn');
Route::get('/validate/nip', 'Api\AuthController@validateNip');
Route::get('/validate/npm', 'Api\AuthController@validateNpm');

Route::get('/email/verify/{id}/{hash}', function (Request $request, $id) {
    $user = User::findOrFail($id);
    if (!hash_equals((string) $request->hash, sha1($user->getEmailForVerification()))) {
        return response()->json(['message' => 'Link verifikasi tidak valid atau sudah kedaluwarsa.'], 400);
    }
    if (!$user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
    }
    return redirect('http://localhost:5173/login?verified=true');
})->middleware(['signed'])->name('verification.verify');

// ==========================================
// PROTECTED ROUTES
// ==========================================
Route::group(['middleware' => ['jwt.verify']], function () {
    Route::post('/logout', 'Api\AuthController@logout');
    Route::get('/get_user', 'Api\AuthController@get_user');
    Route::get('/refresh', 'Api\AuthController@refresh');
    Route::get('/app_modul', 'Api\AppModuleController@index');
    Route::get('/call_user', 'Api\AuthController@call_user');

    // ── SSO: Daftar modul yang dapat diakses user saat ini ──────────────────────
    Route::get('/my-modules', [MyModuleController::class, 'index']);

    // ── SSO: Daftar role yang dimiliki user di modul tertentu ──────────────────
    Route::get('/my-modules/{appModuleId}/roles', [MyModuleController::class, 'getRolesForModule']);

    // Route SSO untuk redirect ke app (Butuh Auth)
    Route::get('/sso/redirect', [AuthController::class, 'redirect']);

    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::post('/update', [ProfileController::class, 'update']);
        Route::post('/change-password', [ProfileController::class, 'changePassword']);
    });

    // Admin Routes
    Route::middleware(['check.role:admin'])->prefix('admins')->name('admins.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/export', [UserController::class, 'export'])->name('export');
        Route::post('/import', [UserController::class, 'import'])->name('import');

        Route::prefix('dashboard')->group(function () {
            Route::get('stats', [DashboardController::class, 'stats']);
            Route::get('active-users', [DashboardController::class, 'activeUsersChart']);
            Route::get('user-growth', [DashboardController::class, 'userGrowth']);
            Route::get('recent-activity', [DashboardController::class, 'recentActivity']);
            Route::get('idle-users', [DashboardController::class, 'idleUsers']);
            Route::get('role-distribution', [DashboardController::class, 'roleDistribution']);
            Route::get('login-heatmap', [DashboardController::class, 'loginHeatmap']);
            Route::post('clear-cache', [DashboardController::class, 'clearCache']);
        });

        Route::prefix('security')->name('security.')->group(function () {
            Route::get('logs', [LoginLogController::class, 'index'])->name('logs.index');
            Route::get('logs/grouped', [LoginLogController::class, 'grouped'])->name('logs.grouped');
            Route::get('logs/user/{id}', [LoginLogController::class, 'byUser'])->name('logs.by-user');
            Route::get('logs/stats', [LoginLogController::class, 'stats'])->name('logs.stats');
            Route::get('suspicious-ips', [LoginLogController::class, 'suspiciousIps'])->name('suspicious-ips');
            Route::get('rate-limit-status', [LoginLogController::class, 'rateLimitStatus'])->name('rate-limit-status');
            Route::delete('logs/purge', [LoginLogController::class, 'purge'])->name('logs.purge');
        });

        Route::prefix('app-modules')->name('app-modules.')->group(function () {
            Route::get('/', [AppModuleController::class, 'index'])->name('index');
            Route::post('/', [AppModuleController::class, 'store'])->name('store');
            Route::get('/{id}', [AppModuleController::class, 'show'])->name('show');
            Route::put('/{id}', [AppModuleController::class, 'update'])->name('update');
            Route::delete('/{id}', [AppModuleController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/reset-secret', [AppModuleController::class, 'resetSecret'])->name('reset-secret');
        });

        // ── Roles ────────────────────────────────────────────────────────────────
        Route::prefix('roles')->name('roles.')->group(function () {
            Route::get('/', [RoleController::class, 'index'])->name('index');
            Route::post('/', [RoleController::class, 'store'])->name('store');
            Route::post('/assign', [RoleController::class, 'assignRole'])->name('assign');
            Route::post('/unassign', [RoleController::class, 'unassignRole'])->name('unassign');
            Route::get('/{id}', [RoleController::class, 'show'])->name('show');
            Route::put('/{id}', [RoleController::class, 'update'])->name('update');
            Route::delete('/{id}', [RoleController::class, 'destroy'])->name('destroy');
        });

        // ── Jabatans ─────────────────────────────────────────────────────────────
        Route::prefix('jabatans')->name('jabatans.')->group(function () {
            Route::get('/', [JabatanController::class, 'index'])->name('index');
            Route::post('/', [JabatanController::class, 'store'])->name('store');
            Route::get('/{id}', [JabatanController::class, 'show'])->name('show');
            Route::put('/{id}', [JabatanController::class, 'update'])->name('update');
            Route::delete('/{id}', [JabatanController::class, 'destroy'])->name('destroy');
        });

        // ── Units ────────────────────────────────────────────────────────────────
        Route::prefix('units')->name('units.')->group(function () {
            Route::get('/', [UnitController::class, 'index'])->name('index');
            Route::post('/', [UnitController::class, 'store'])->name('store');
            Route::get('/{id}', [UnitController::class, 'show'])->name('show');
            Route::put('/{id}', [UnitController::class, 'update'])->name('update');
            Route::delete('/{id}', [UnitController::class, 'destroy'])->name('destroy');
        });

        // ── User Unit Assignments ───────────────────────────────────────────────
        Route::post('/{id}/assign-unit', [UserController::class, 'assignUnit'])->name('assign-unit');
        Route::post('/{id}/unassign-unit', [UserController::class, 'unassignUnit'])->name('unassign-unit');

        // ── Permissions ──────────────────────────────────────────────────────────
        Route::prefix('permissions')->name('permissions.')->group(function () {
            Route::get('/', [PermissionController::class, 'index'])->name('index');
            Route::post('/bulk', [PermissionController::class, 'bulkStore'])->name('bulk-store');
            Route::put('/bulk', [PermissionController::class, 'bulkUpdate'])->name('bulk-update');
            Route::delete('/bulk', [PermissionController::class, 'bulkDestroy'])->name('bulk-destroy');
            Route::post('/', [PermissionController::class, 'store'])->name('store');
            Route::get('/{id}', [PermissionController::class, 'show'])->name('show');
            Route::put('/{id}', [PermissionController::class, 'update'])->name('update');
            Route::delete('/{id}', [PermissionController::class, 'destroy'])->name('destroy');
        });

        // ── Role ↔ Permission Assignments ────────────────────────────────────────
        Route::prefix('role-permissions')->name('role-permissions.')->group(function () {
            Route::get('/', [RoleHasPermissionController::class, 'index'])->name('index');
            Route::post('/assign', [RoleHasPermissionController::class, 'assign'])->name('assign');
            Route::post('/unassign', [RoleHasPermissionController::class, 'unassign'])->name('unassign');
            Route::post('/sync', [RoleHasPermissionController::class, 'sync'])->name('sync');
        });

        Route::prefix('sso-keys')->name('sso-keys.')->group(function () {
            Route::get('/',        [SsoIntegrationController::class, 'index'])->name('index');
            Route::post('/',       [SsoIntegrationController::class, 'store'])->name('store');
            Route::get('/{id}',    [SsoIntegrationController::class, 'show'])->name('show');
            Route::put('/{id}',    [SsoIntegrationController::class, 'update'])->name('update');
            Route::delete('/{id}', [SsoIntegrationController::class, 'destroy'])->name('destroy');
        });

        // Wildcard user routes moved to the end to prevent hijacking static routes
        Route::get('/{id}', [UserController::class, 'show'])->name('show');
        Route::match(['POST', 'PUT'], '/{id}', [UserController::class, 'update'])->name('update');
        Route::delete('/{id}', [UserController::class, 'destroy'])->name('destroy');

        Route::patch('/{id}/toggle-active', [UserController::class, 'toggleActive'])->name('toggle-active');
        Route::post('/{id}/reset-password', [UserController::class, 'resetPassword'])->name('reset-password');
        Route::get('/{id}/activity-logs', [UserController::class, 'activityLogs'])->name('activity-logs');
    });
});
