<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use JWTAuth;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

use App\Models\User;
use App\Models\TxUserModulPermission;

use App\Http\Helper\ResponseBuilder;
use App\Services\LoginLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth as FacadesJWTAuth;
use Illuminate\Auth\Events\Registered;

use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Cache;
use App\Services\ActivityLogService;

class AuthController extends Controller
{
    public function __construct(
        protected LoginLogService $loginLogService,
        protected ActivityLogService $activityLog,
    ) {}

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string|min:8|confirmed',
            'role'     => 'required|in:Mahasiswa,Dosen,Admin,Pegawai,Dosen_Ext',
            'nidn'     => 'nullable|string',
            'npm'      => 'nullable|string',
            'nama'     => 'nullable|string|max:255', // ← nama dari hasil validasi (Mahasiswa/Dosen/Pegawai)
            // Field khusus Dosen Eksternal
            'nama_lengkap'  => 'required_if:role,Dosen_Ext|nullable|string|max:255',
            'nik'           => 'required_if:role,Dosen_Ext|nullable|string|max:60',
            'instansi'      => 'required_if:role,Dosen_Ext|nullable|string|max:255',
            'jenkel'        => 'nullable|in:L,P',
            'tanggal_lahir' => 'nullable|date',
            'tempat_lahir'  => 'nullable|string|max:255',
            'agama'         => 'nullable|string|max:25',
            'no_hp'         => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'message' => $validator->errors()->first()], 422);
        }

        $isDosenExt = $request->role === 'Dosen_Ext';

        // Cek email sudah ada di UCL
        $exists = DB::connection('ucl')
            ->table('tb_users')
            ->where('email', $request->email)
            ->whereNull('deleted_at')
            ->exists();

        if ($exists) {
            return response()->json(['status' => 422, 'message' => 'Email sudah terdaftar.'], 422);
        }

        // Cek NIK duplikat di tb_data_pribadi (khusus Dosen_Ext)
        if ($isDosenExt && $request->nik) {
            $nikExists = DB::connection('ucl')
                ->table('tb_data_pribadi')
                ->where('nik', $request->nik)
                ->exists();

            if ($nikExists) {
                return response()->json(['status' => 422, 'message' => 'NIK/NIP sudah terdaftar.'], 422);
            }
        }

        $nidnToSave = $isDosenExt ? null : $request->nidn;
        $npmToSave  = $isDosenExt ? null : $request->npm;

        if ($npmToSave) {
            $npmExists = DB::connection('ucl')
                ->table('tb_users')
                ->where('npm', $npmToSave)
                ->whereNull('deleted_at')
                ->exists();

            if ($npmExists) {
                return response()->json(['status' => 422, 'message' => 'NPM sudah terdaftar. Silakan login.'], 422);
            }
        }

        if ($nidnToSave) {
            $nidnExists = DB::connection('ucl')
                ->table('tb_users')
                ->where('nidn', $nidnToSave)
                ->whereNull('deleted_at')
                ->exists();

            if ($nidnExists) {
                return response()->json(['status' => 422, 'message' => 'NIDN sudah terdaftar. Silakan login.'], 422);
            }
        }

        $isverified = $isDosenExt ? false : true;
        $userId = (string) \Illuminate\Support\Str::uuid();

        DB::beginTransaction();
        try {
            // Insert ke tb_users
            DB::connection('ucl')->table('tb_users')->insert([
                'user_id'    => $userId,
                'email'      => $request->email,
                'password'   => Hash::make($request->password),
                'role'       => $request->role,
                'nidn'       => $nidnToSave,
                'npm'        => $npmToSave,
                'isverified' => $isverified,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($isDosenExt) {
                // Dosen Eksternal — form lengkap
                DB::connection('ucl')->table('tb_data_pribadi')->insert([
                    'dp_id'         => (string) \Illuminate\Support\Str::uuid(),
                    'user_id'       => $userId,
                    'nama_lengkap'  => $request->nama_lengkap,
                    'jenkel'        => $request->jenkel,
                    'tanggal_lahir' => $request->tanggal_lahir,
                    'tempat_lahir'  => $request->tempat_lahir,
                    'agama'         => $request->agama,
                    'email'         => $request->email,
                    'no_hp'         => $request->no_hp,
                    'nik'           => $request->nik,
                    'instansi_ext'  => $request->instansi,
                ]);
            } else {
                // Mahasiswa/Dosen/Pegawai — minimal nama dari hasil validasi
                DB::connection('ucl')->table('tb_data_pribadi')->insert([
                    'dp_id'        => (string) \Illuminate\Support\Str::uuid(),
                    'user_id'      => $userId,
                    'nama_lengkap' => $request->nama,
                    'email'        => $request->email,
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 500, 'message' => 'Registrasi gagal: ' . $e->getMessage()], 500);
        }

        $message = $isDosenExt
            ? 'Registrasi berhasil. Akun Anda menunggu verifikasi oleh admin.'
            : 'Registrasi berhasil.';

        return response()->json(['status' => 201, 'message' => $message], 201);
    }

    public function auth(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 400,
                'message' => 'Email dan password harus diisi dengan benar.',
                'data'    => $validator->errors()
            ], 400);
        }

        if ($this->loginLogService->isIpBlocked($request->ip())) {
            $remaining = $this->loginLogService->getLockoutRemainingSeconds($request->ip(), 'ip');
            return response()->json([
                'status'  => 429,
                'message' => "Terlalu banyak percobaan login. Coba lagi dalam {$remaining} detik.",
                'data'    => []
            ], 429);
        }

        if ($this->loginLogService->isEmailBlocked($request->email)) {
            $remaining = $this->loginLogService->getLockoutRemainingSeconds($request->email, 'email');
            return response()->json([
                'status'  => 429,
                'message' => "Akun ini sementara dikunci. Coba lagi dalam {$remaining} detik.",
                'data'    => []
            ], 429);
        }

        // ── Cek credential ke DB UCL ──────────────────────────────────────────
        $uclUser = DB::connection('ucl')
            ->table('tb_users')
            ->where('email', $request->email)
            ->whereNull('deleted_at')
            ->first();

        if (!$uclUser) {
            $this->loginLogService->logFailure($request, 'invalid_credentials');
            return response()->json([
                'status'  => 401,
                'message' => 'Email atau password salah.',
                'data'    => []
            ], 401);
        }

        if (!Hash::check($request->password, $uclUser->password)) {
            $this->loginLogService->logFailure($request, 'invalid_credentials');
            return response()->json([
                'status'  => 401,
                'message' => 'Email atau password salah.',
                'data'    => []
            ], 401);
        }

        if (!$uclUser->isverified) {
            $this->loginLogService->logFailure($request, 'email_not_verified');
            return response()->json([
                'status'  => 403,
                'message' => 'Akun belum diverifikasi.',
                'data'    => []
            ], 403);
        }

        // Fetch User model using Eloquent on UCL connection
        $user = User::where('email', $uclUser->email)->first();
        if (!$user) {
            // Backup fallback just in case Eloquent model wasn't resolved
            return response()->json([
                'status'  => 500,
                'message' => 'User account configuration error.',
                'data'    => []
            ], 500);
        }

        // Generate JWT
        try {
            $token = FacadesJWTAuth::fromUser($user);
        } catch (JWTException $e) {
            return response()->json([
                'status'  => 500,
                'message' => 'Sistem error, tidak dapat membuat token.',
                'data'    => []
            ], 500);
        }

        $this->loginLogService->logSuccess($user->user_id, $request);

        $this->activityLog->log(
            ActivityLogService::TYPE_LOGIN,
            'Login ke E-Portal',
            userId: $user->user_id,
            actorId: $user->user_id,
            metadata: ['ip' => $request->ip(), 'browser' => $request->userAgent()],
        );

        $isProduction = config('app.env') === 'production';
        $cookieDomain = $isProduction ? '.uika-bogor.ac.id' : null;

        $cookie = cookie(
            'uika_sso_token',
            $token,
            1440,
            '/',
            $cookieDomain,
            $isProduction,
            true,
            false,
            $isProduction ? 'None' : 'Lax'
        );

        $roleName = $user->getRoleNames()->first() ?? $user->role;
        $roleModel = $user->roles()->first();

        return ResponseBuilder::success(200, 'Login berhasil', [
            'user' => [
                'id'        => $user->user_id,
                'email'     => $user->email,
                'role'      => $roleName,
                'role_id'   => $roleModel ? $roleModel->id : null,
                'nidn'      => $user->nidn,
                'npm'       => $user->npm,
                'unit_id'   => $user->unit_id,
                'unit_name' => $user->unit?->nama_unit,
            ],
            'uika_sso_token' => $token,
        ])->withCookie($cookie);
    }

    public function logout(Request $request)
    {
        try {
            $token = $request->bearerToken() ?? $request->cookie('uika_sso_token');
            \Cache::forget('jwt_user_' . md5($token ?? ''));

            FacadesJWTAuth::parseToken()->invalidate();

            $cookie = cookie()->forget('uika_sso_token');

            $this->activityLog->logForCurrentUser(
                ActivityLogService::TYPE_LOGOUT,
                'Logout dari E-Portal',
            );

            return response()->json([
                'status' => 200,
                'success' => true,
                'message' => 'User logged out successfully',
                'data' => []
            ], 200)->withCookie($cookie);
        } catch (TokenInvalidException $e) {
            return response()->json([
                'status' => 400,
                'success' => false,
                'message' => 'Session has expired or token is invalid.',
                'data' => []
            ], 400);
        } catch (JWTException $e) {
            return response()->json([
                'status' => 500,
                'success' => false,
                'message' => 'An error occurred on the server while logging out.',
                'data' => []
            ], 500);
        } catch (\Exception $e) {
            \Log::error('Logout error: ' . $e->getMessage());
            return response()->json([
                'status' => 500,
                'success' => false,
                'message' => $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    public function get_user(Request $request)
    {
        try {
            $user = FacadesJWTAuth::user();

            // Eager load relasi agar unit_id dan unit_name bisa diakses dengan benar
            $user->load(['roles', 'unit']);

            $unit     = $user->getRelation('unit');
            $jabatans = $user->getRoleNames()->toArray(); // Hanya dari Spatie, tidak fallback ke role lama

            $userData = [
                'id'        => $user->user_id,
                'email'     => $user->email,
                'name'      => $user->email,
                'role'      => !empty($jabatans) ? $jabatans[0] : null, // null jika tidak punya jabatan
                'role_id'   => $user->roles()->first()?->id,
                'nidn'      => $user->nidn,
                'npm'       => $user->npm,
                'unit_id'   => $unit?->id,
                'unit_name' => $unit?->nama_unit,
                'jabatans'  => $jabatans, // Array jabatan aktual dari Spatie
                'image'     => null,
            ];

            // Ambil semua permission user (jika admin/super-admin, ambil semua permission di system)
            if (in_array(strtolower($user->role ?? ''), ['admin', 'super-admin']) || $user->hasAnyRole(['admin', 'super-admin'])) {
                $allPermissions = \App\Models\Permission::all();
            } else {
                $allPermissions = $user->getAllPermissions();
            }

            // Buat list nama permission flat
            $permissionsList = $allPermissions->pluck('name')->toArray();

            // Group permission berdasarkan appModule_id
            $permissionsByModule = [];
            foreach ($allPermissions as $permission) {
                if ($permission->appModule_id) {
                    $permissionsByModule[$permission->appModule_id][] = $permission->name;
                }
            }
        );

            // Filter jika ada appModule_id di query parameter
            $appModuleId = $request->query('appModule_id');
            $modulePermissions = [];
            if ($appModuleId) {
                $modulePermissions = isset($permissionsByModule[$appModuleId]) ? $permissionsByModule[$appModuleId] : [];
            }

            // Accessible Modules
            $accessibleModuleIds = array_keys($permissionsByModule);
            $accessibleModulesData = \App\Models\AppModule::whereIn('id', $accessibleModuleIds)
                ->orderBy('name')
                ->get()
                ->map(fn($mod) => [
                    'id'          => $mod->id,
                    'name'        => $mod->name,
                    'url'         => $mod->url,
                    'permissions' => $permissionsByModule[$mod->id] ?? [],
                ])
                ->values();

            $userData['permissions'] = $permissionsList;
            $userData['permissions_by_module'] = $permissionsByModule;
            $userData['module_permissions'] = $modulePermissions;
            $userData['accessible_modules'] = $accessibleModulesData;

            return response()->json([
                'status' => 200,
                'success' => true,
                'message' => 'User data retrieved successfully',
                'data' => $userData
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'success' => false,
                'message' => 'Failed to retrieve user data: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }
}

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            // Cek email ke DB UCL langsung via User model
            $user = User::where('email', $googleUser->email)
                ->whereNull('deleted_at')
                ->first();

            if (!$user) {
                return redirect('http://localhost:5173/login?error=AkunTidakTerdaftar');
            }

            if (!$user->isverified) {
                return redirect('http://localhost:5173/login?error=AkunBelumVerifikasi');
            }

            $token = FacadesJWTAuth::fromUser($user);

            $isProduction = config('app.env') === 'production';
            $cookieDomain = $isProduction ? '.uika-bogor.ac.id' : null;

            $cookie = cookie(
                'uika_sso_token',
                $token,
                1440,
                '/',
                $cookieDomain,
                $isProduction,
                true,
                false,
                $isProduction ? 'None' : 'Lax'
            );

            return redirect('http://localhost:5173/auth/google/success')
                ->withCookie($cookie);
        } catch (\Exception $e) {
            \Log::error('Google login error: ' . $e->getMessage());
            return redirect('http://localhost:5173/login?error=GoogleLoginFailed');
        }
    }

    public function tokenFromCookie(Request $request)
    {
        $token = $request->cookie('uika_sso_token') ?: $request->bearerToken();

        if (!$token) {
            return response()->json(['status' => 401, 'message' => 'No cookie found.'], 401);
        }

        try {
            $user = FacadesJWTAuth::setToken($token)->authenticate();

            if (!$user) {
                return response()->json(['status' => 401, 'message' => 'Invalid token.'], 401);
            }

            $roleName = $user->getRoleNames()->first() ?? $user->role;
            $roleModel = $user->roles()->first();

            return response()->json([
                'status' => 200,
                'data'   => [
                    'uika_sso_token' => $token,
                    'user' => [
                        'id'        => $user->user_id,
                        'email'     => $user->email,
                        'role'      => $roleName,
                        'role_id'   => $roleModel ? $roleModel->id : null,
                        'nidn'      => $user->nidn,
                        'npm'       => $user->npm,
                        'unit_id'   => $user->unit_id,
                        'unit_name' => $user->unit?->nama_unit,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 401, 'message' => 'Token invalid.'], 401);
        }
    }

    public function redirect(Request $request)
    {
        try {
            $token = $request->cookie('uika_sso_token') ?: $request->bearerToken();

            if (!$token) {
                return response()->json([
                    'status' => 401,
                    'message' => 'No active session found.',
                ], 401);
            }

            $user = FacadesJWTAuth::setToken($token)->authenticate();

            $targetUrl    = $request->query('target_url');
            $role_id      = $request->query('role_id');
            $appModule_id = $request->query('appModule_id');

            if (!$targetUrl || !$role_id || !$appModule_id) {
                return response()->json([
                    'status'  => 400,
                    'message' => 'Missing required parameters.',
                ], 400);
            }

            // Authorization check: Verify user actually possesses this role/jabatan mapping locally,
            // unless they are a global administrator.
            $isGlobalAdmin = in_array(strtolower($user->role ?? ''), ['admin', 'super-admin']) || $user->hasAnyRole(['admin', 'super-admin']);
            if (!$isGlobalAdmin) {
                $hasAssignment = \App\Models\UserJabatanUnit::where([
                    'user_id'    => $user->user_id,
                    'jabatan_id' => $role_id,
                ])->exists();

                if (!$hasAssignment) {
                    return response()->json([
                        'status'  => 403,
                        'message' => 'You are not assigned to this role/jabatan.',
                    ], 403);
                }
            }

            // Get role model details for metadata
            // $roleModel = \App\Models\Role::find($role_id);
            $roleModel = null;

            // Calculate the permissions for this user, module, and role context
            $permissions = $this->getPermissionsForContext($user, $appModule_id, $role_id);

            // Ambil unit yang sesuai dengan jabatan terpilih dari trx_user_jabatan_unit
            $assignment = \App\Models\UserJabatanUnit::where([
                'user_id'    => $user->user_id,
                'jabatan_id' => $role_id,
            ])->first();

            $unitId   = $assignment?->unit_id ?? $user->unit_id;
            $unitName = $assignment?->unit?->nama_unit ?? $user->unit?->nama_unit;
            $unitCode = $assignment?->unit?->code ?? $user->unit?->code;

            // Generate a scoped token for the sub-app containing the contextual permissions
            $scopedToken = FacadesJWTAuth::claims([
                'id'           => $user->user_id,
                'email'        => $user->email,
                'appModule_id' => (int) $appModule_id,
                'role_id'      => (int) $role_id,
                'role_name'    => $roleModel?->name,
                'unit_id'      => $unitId ? (int) $unitId : null,
                'unit_name'    => $unitName,
                'unit_code'    => $unitCode,
                'permissions'  => $permissions,
                'is_scoped'    => true, // flag to identify scoped token
            ])->fromUser($user);

            // Redirect ke aplikasi tujuan dengan scoped token di URL
            $redirectUrl = $targetUrl . '?' . http_build_query([
                'token'        => $scopedToken,
                'role_id'      => $role_id,
                'appModule_id' => $appModule_id,
            ]);

            return response()->json([
                'status'       => 200,
                'redirect_url' => $redirectUrl,
            ]);
        } catch (\Exception $e) {
            \Log::error('SSO Redirect Error: ' . $e->getMessage());
            return response()->json([
                'status'  => 401,
                'message' => 'Session invalid or expired. Error: ' . $e->getMessage(),
            ], 401);
        }
    }

    public function validateNidn(Request $request)
{
    $nidn = $request->query('nidn');
    if (!$nidn) return response()->json(['status' => 400, 'valid' => false, 'message' => 'NIDN wajib diisi.'], 400);

    try {
        $response = Http::withHeaders(['X-API-Key' => config('services.simpeg.api_key')])
            ->get(config('services.simpeg.url') . '/api/external/validate/nidn', ['nidn' => $nidn]);
        return response()->json($response->json(), $response->status());
    } catch (\Exception $e) {
        return response()->json(['status' => 500, 'valid' => false, 'message' => 'Gagal konek ke SIMPEG.'], 500);
    }
}

public function validateNip(Request $request)
{
    $nip = $request->query('nip');
    if (!$nip) return response()->json(['status' => 400, 'valid' => false, 'message' => 'NIP wajib diisi.'], 400);

    try {
        $response = Http::withHeaders(['X-API-Key' => config('services.simpeg.api_key')])
            ->get(config('services.simpeg.url') . '/api/external/validate/nip', ['nip' => $nip]);
        return response()->json($response->json(), $response->status());
    } catch (\Exception $e) {
        return response()->json(['status' => 500, 'valid' => false, 'message' => 'Gagal konek ke SIMPEG.'], 500);
    }
}

public function validateNpm(Request $request)
{
    $npm = $request->query('npm');
    if (!$npm) return response()->json(['status' => 400, 'valid' => false, 'message' => 'NPM wajib diisi.'], 400);

    try {
        $response = Http::get(config('services.siakad.url') . '/api/external/validate/npm', ['npm' => $npm]);
        return response()->json($response->json(), $response->status());
    } catch (\Exception $e) {
        return response()->json(['status' => 500, 'valid' => false, 'message' => 'Gagal konek ke SIAKAD.'], 500);
    }
}

    private function getPermissionsForContext($user, $appModuleId, $roleId): array
    {
        // If user is admin/super-admin globally, grant all permissions of the module
        if (in_array(strtolower($user->role ?? ''), ['admin', 'super-admin']) || $user->hasAnyRole(['admin', 'super-admin'])) {
            return \App\Models\Permission::where('appModule_id', $appModuleId)
                ->pluck('name')
                ->toArray();
        }

        // 1. Get permissions assigned to the role
        $rolePermissionIds = \App\Models\RoleHasPermission::where('role_id', $roleId)
            ->pluck('permission_id')
            ->toArray();

        // 2. Fetch names of permissions that belong to this appModule_id
        return \App\Models\Permission::whereIn('id', $rolePermissionIds)
            ->where('appModule_id', $appModuleId)
            ->pluck('name')
            ->toArray();
    }
}
