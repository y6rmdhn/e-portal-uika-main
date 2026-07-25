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

    public function checkId(Request $request)
    {
        $field = $request->query('field'); // 'npm' atau 'nidn'
        $value = $request->query('value');

        if (!in_array($field, ['npm', 'nidn'])) {
            return response()->json(['exists' => false], 200);
        }

        $exists = DB::connection('pgsql')
            ->table('tb_users')
            ->whereRaw("TRIM({$field}) = ?", [trim($value)])
            ->whereNull('deleted_at')
            ->exists();

        return response()->json(['exists' => $exists], 200);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'      => 'required|email',
            'password'   => 'required|string|min:8|confirmed',
            'role'       => 'required|in:Mahasiswa,Dosen,Admin,Pegawai,Dosen_Ext',
            'nidn'       => 'nullable|string',
            'npm'        => 'nullable|string',
            'nama'       => 'nullable|string|max:255',
            'jabatan_id' => 'nullable|integer|exists:m_jabatan,id',
            'unit_code'  => 'nullable|string|max:50',
            'unit_nama'  => 'nullable|string|max:100',
            'nama_lengkap'  => 'required_if:role,Dosen_Ext|nullable|string|max:255',
            'nik'           => 'required_if:role,Dosen_Ext|nullable|string|max:60',
            'instansi'      => 'required_if:role,Dosen_Ext|nullable|string|max:255',
            'asal_univ'     => 'nullable|string|max:255',
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

        $exists = DB::connection('pgsql')
            ->table('tb_users')
            ->where('email', $request->email)
            ->whereNull('deleted_at')
            ->exists();

        if ($exists) {
            return response()->json(['status' => 422, 'message' => 'Email sudah terdaftar.'], 422);
        }

        if ($isDosenExt && $request->nik) {
            $nikExists = DB::connection('pgsql')
                ->table('tb_data_pribadi')
                ->where('nik', trim($request->nik))
                ->exists();

            if ($nikExists) {
                return response()->json(['status' => 422, 'message' => 'NIK/NIP sudah terdaftar.'], 422);
            }
        }

        $nidnToSave = $isDosenExt ? null : $request->nidn;
        $npmToSave  = $isDosenExt ? null : $request->npm;

        if ($npmToSave) {
            $npmExists = DB::connection('pgsql')
                ->table('tb_users')
                ->whereRaw("TRIM(npm) = ?", [trim($npmToSave)])
                ->whereNull('deleted_at')
                ->exists();

            if ($npmExists) {
                return response()->json(['status' => 422, 'message' => 'NPM sudah terdaftar. Silakan login.'], 422);
            }
        }

        if ($nidnToSave) {
            $nidnExists = DB::connection('pgsql')
                ->table('tb_users')
                ->whereRaw("TRIM(nidn) = ?", [trim($nidnToSave)])
                ->whereNull('deleted_at')
                ->exists();

            if ($nidnExists) {
                return response()->json(['status' => 422, 'message' => 'NIDN sudah terdaftar. Silakan login.'], 422);
            }
        }

        $oldUserByEmail = DB::connection('pgsql')
            ->table('tb_users')
            ->where('email', $request->email)
            ->whereNotNull('deleted_at')
            ->first();

        if ($oldUserByEmail) {
            DB::connection('pgsql')->table('tb_data_pribadi')->where('user_id', $oldUserByEmail->user_id)->delete();
            DB::connection('pgsql')->table('trx_user_jabatan_unit')->where('user_id', $oldUserByEmail->user_id)->delete();
            DB::connection('pgsql')->table('tb_users')->where('user_id', $oldUserByEmail->user_id)->delete();
        }

        if ($nidnToSave) {
            $oldUserByNidn = DB::connection('pgsql')
                ->table('tb_users')
                ->whereRaw("TRIM(nidn) = ?", [trim($nidnToSave)])
                ->whereNotNull('deleted_at')
                ->first();

            if ($oldUserByNidn) {
                DB::connection('pgsql')->table('tb_data_pribadi')->where('user_id', $oldUserByNidn->user_id)->delete();
                DB::connection('pgsql')->table('trx_user_jabatan_unit')->where('user_id', $oldUserByNidn->user_id)->delete();
                DB::connection('pgsql')->table('tb_users')->where('user_id', $oldUserByNidn->user_id)->delete();
            }
        }

        if ($npmToSave) {
            $oldUserByNpm = DB::connection('pgsql')
                ->table('tb_users')
                ->whereRaw("TRIM(npm) = ?", [trim($npmToSave)])
                ->whereNotNull('deleted_at')
                ->first();

            if ($oldUserByNpm) {
                DB::connection('pgsql')->table('tb_data_pribadi')->where('user_id', $oldUserByNpm->user_id)->delete();
                DB::connection('pgsql')->table('trx_user_jabatan_unit')->where('user_id', $oldUserByNpm->user_id)->delete();
                DB::connection('pgsql')->table('tb_users')->where('user_id', $oldUserByNpm->user_id)->delete();
            }
        }

        $isverified = $isDosenExt ? false : true;
        $userId = (string) \Illuminate\Support\Str::uuid();

        DB::beginTransaction();
        try {
            DB::connection('pgsql')->table('tb_users')->insert([
                'user_id'    => $userId,
                'email'      => $request->email,
                'password'   => Hash::make($request->password),
                'role'       => $request->role,
                'nidn'       => $nidnToSave ? trim($nidnToSave) : null,
                'npm'        => $npmToSave ? trim($npmToSave) : null,
                'isverified' => $isverified,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($isDosenExt) {
                DB::connection('pgsql')->table('tb_data_pribadi')->insert([
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
                DB::connection('pgsql')->table('tb_data_pribadi')->insert([
                    'dp_id'        => (string) \Illuminate\Support\Str::uuid(),
                    'user_id'      => $userId,
                    'nama_lengkap' => $request->nama,
                    'email'        => $request->email,
                    'instansi_ext' => $request->asal_univ,
                ]);
            }

            DB::commit();

            // ── Auto-assign jabatan ──
            $jabatanMap = [
                'Mahasiswa'  => 102,
                'Dosen'      => 21,
                'Dosen_Ext'  => 21,
            ];

            $jabatanId = $request->jabatan_id ?? ($jabatanMap[$request->role] ?? null);
            $newUser = \App\Models\User::where('user_id', $userId)->first();

            if ($jabatanId && $newUser) {
                $jabatan = \App\Models\Jabatan::find($jabatanId);
                if ($jabatan) {
                    try {
                        $newUser->assignRole($jabatan->name);
                    } catch (\Exception $e) {
                        \Log::warning('Auto-assign role gagal: ' . $e->getMessage());
                    }
                }

                // ← tambah ini: update role_id di tb_users pgsql
                DB::connection('pgsql')
                    ->table('tb_users')
                    ->where('user_id', $userId)
                    ->update(['role_id' => $jabatanId]);
            }

            // ── Auto-assign unit ──
            if ($request->unit_code && $request->unit_nama) {
                $unit = \App\Models\Unit::where('code', $request->unit_code)->first();

                if (!$unit) {
                    $unit = \App\Models\Unit::create([
                        'code'      => $request->unit_code,
                        'nama_unit' => $request->unit_nama,
                    ]);
                }

                if ($unit && $jabatanId && $newUser) {
                    \App\Models\UserJabatanUnit::firstOrCreate([
                        'user_id'    => $userId,
                        'jabatan_id' => $jabatanId,
                        'unit_id'    => $unit->id,
                    ]);

                    DB::connection('pgsql')
                        ->table('tb_users')
                        ->where('user_id', $userId)
                        ->update(['department_code' => $unit->code]);
                }
            }
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

        DB::connection('pgsql')
            ->table('tb_users')
            ->where('user_id', $user->user_id)
            ->update(['last_login_at' => now()]);

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

            // Ambil user_id sebelum token di-invalidate
            $user = FacadesJWTAuth::parseToken()->authenticate();
            $userId = $user?->user_id;

            FacadesJWTAuth::parseToken()->invalidate();

            $cookie = cookie()->forget('uika_sso_token');

            // ── Back-Channel Logout ke semua aplikasi terintegrasi ──
            if ($userId) {
                $secret = env('SIMPEG_API_KEY', 'secret_sso_uika');
                $apps = [
                    env('SIMPEG_API_URL', 'http://localhost:3000') . '/api/sso/logout',
                    env('SIAKAD_API_URL', 'http://localhost:3000') . '/api/sso/logout',
                    env('ELIBRARY_URL', 'http://localhost:8001') . '/sso/logout',
                    env('UCL_URL', 'http://localhost:4242') . '/sso/logout',
                ];

                foreach ($apps as $url) {
                    try {
                        \Log::info("SLO: hitting {$url} untuk user {$userId}");
                        $response = \Illuminate\Support\Facades\Http::timeout(3)->post($url, [
                            'user_id' => $userId,
                            'secret'  => $secret,
                        ]);
                        \Log::info("SLO response dari {$url}: " . $response->status());
                    } catch (\Exception $e) {
                        \Log::warning("SLO gagal ke {$url}: " . $e->getMessage());
                    }
                }
            }

            $this->activityLog->logForCurrentUser(
                ActivityLogService::TYPE_LOGOUT,
                'Logout dari E-Portal',
            );

            return response()->json([
                'status'  => 200,
                'success' => true,
                'message' => 'User logged out successfully',
                'data'    => []
            ], 200)->withCookie($cookie);
        } catch (TokenInvalidException $e) {
            return response()->json([
                'status'  => 400,
                'success' => false,
                'message' => 'Session has expired or token is invalid.',
                'data'    => []
            ], 400);
        } catch (JWTException $e) {
            return response()->json([
                'status'  => 500,
                'success' => false,
                'message' => 'An error occurred on the server while logging out.',
                'data'    => []
            ], 500);
        } catch (\Exception $e) {
            \Log::error('Logout error: ' . $e->getMessage());
            return response()->json([
                'status'  => 500,
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => []
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

            $dataPribadi = DB::connection('pgsql')
                ->table('tb_data_pribadi')
                ->where('user_id', $user->user_id)
                ->first();

            $userData = [
                'id'           => $user->user_id,
                'email'        => $user->email,
                'name'         => $dataPribadi?->nama_lengkap ?? $user->email,
                'nama_lengkap' => $dataPribadi?->nama_lengkap ?? null,
                'no_hp'        => $dataPribadi?->no_hp ?? null,
                'image'        => $dataPribadi?->image ?? null,
                'role'         => !empty($jabatans) ? $jabatans[0] : null,
                'role_id'      => $user->roles()->first()?->id,
                'nidn'         => $user->nidn,
                'npm'          => $user->npm,
                'unit_id'      => $unit?->id,
                'unit_name'    => $unit?->nama_unit,
                'jabatans'     => $jabatans,
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

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
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
                return redirect('http://103.158.196.79/eportal/login?error=AkunTidakTerdaftar');
            }

            if (!$user->isverified) {
                return redirect('http://103.158.196.79/eportal/login?error=AkunBelumVerifikasi');
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

            return redirect('http://103.158.196.79/eportal/auth/google/success')
                ->withCookie($cookie);
        } catch (\Exception $e) {
            \Log::error('Google login error: ' . $e->getMessage());
            return redirect('http://103.158.196.79/eportal/login?error=GoogleLoginFailed');
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
            $roleModel = \App\Models\Role::find($role_id);

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
                'role'         => $roleModel?->name, // Overwrite default user role with contextual role name
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

    public function sendResetLinkEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'success' => false,
                'message' => $validator->errors()->first(),
                'data' => []
            ], 400);
        }

        $email = $request->email;

        // Cek user ada di pgsql
        $uclUser = DB::connection('pgsql')
            ->table('tb_users')
            ->where('email', $email)
            ->whereNull('deleted_at')
            ->first();

        if (!$uclUser) {
            return response()->json([
                'status' => 400,
                'success' => false,
                'message' => 'Email tidak ditemukan.',
                'data' => []
            ], 400);
        }

        try {
            $token = Str::random(64);

            DB::table('password_resets')->where('email', $email)->delete();
            DB::table('password_resets')->insert([
                'email'      => $email,
                'token'      => Hash::make($token),
                'created_at' => now(),
            ]);

            $resetUrl = rtrim(config('app.frontend_url'), '/') . "/reset-password?token={$token}&email=" . urlencode($email);

            \Illuminate\Support\Facades\Mail::html(
                view('emails.reset-password', [
                    'resetUrl' => $resetUrl,
                    'email'    => $email,
                ])->render(),
                function ($message) use ($email) {
                    $message->to($email)->subject('Reset Password — E-Portal UIKA');
                }
            );

            return response()->json([
                'status' => 200,
                'success' => true,
                'message' => 'Password reset link sent successfully. Please check your email.',
                'data' => []
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'success' => false,
                'message' => 'An error occurred while sending the password reset link: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 400,
                'success' => false,
                'message' => $validator->errors()->first(),
                'data'    => []
            ], 400);
        }

        $email = $request->email;
        $token = $request->token;

        $resetRecord = DB::table('password_resets')->where('email', $email)->first();

        if (!$resetRecord) {
            return response()->json([
                'status'  => 400,
                'success' => false,
                'message' => 'Token reset tidak valid atau sudah kedaluwarsa.',
                'data'    => []
            ], 400);
        }

        if (!Hash::check($token, $resetRecord->token)) {
            return response()->json([
                'status'  => 400,
                'success' => false,
                'message' => 'Token reset tidak valid.',
                'data'    => []
            ], 400);
        }

        if (now()->diffInMinutes($resetRecord->created_at) > 60) {
            DB::table('password_resets')->where('email', $email)->delete();
            return response()->json([
                'status'  => 400,
                'success' => false,
                'message' => 'Token reset sudah kedaluwarsa. Silakan minta link baru.',
                'data'    => []
            ], 400);
        }

        // Update password di pgsql
        DB::connection('pgsql')
            ->table('tb_users')
            ->where('email', $email)
            ->update(['password' => Hash::make($request->password)]);

        // Hapus token setelah dipakai
        DB::table('password_resets')->where('email', $email)->delete();

        return response()->json([
            'status'  => 200,
            'success' => true,
            'message' => 'Password has been reset successfully.',
            'data'    => []
        ], 200);
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
