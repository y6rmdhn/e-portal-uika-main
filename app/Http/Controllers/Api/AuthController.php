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
            'role'     => 'required|in:Mahasiswa,Dosen,Admin',
            'nidn'     => 'nullable|string',
            'npm'      => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'message' => $validator->errors()->first()], 422);
        }

        // Cek email sudah ada di UCL
        $exists = DB::connection('ucl')
            ->table('tb_users')
            ->where('email', $request->email)
            ->whereNull('deleted_at')
            ->exists();

        if ($exists) {
            return response()->json(['status' => 422, 'message' => 'Email sudah terdaftar.'], 422);
        }

        // Insert ke tb_users UCL
        DB::connection('ucl')->table('tb_users')->insert([
            'user_id'    => \Illuminate\Support\Str::uuid(),
            'email'      => $request->email,
            'password'   => Hash::make($request->password),
            'role'       => $request->role,
            'nidn'       => $request->nidn,
            'npm'        => $request->npm,
            'isverified' => true, // langsung verified
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['status' => 201, 'message' => 'Registrasi berhasil.'], 201);
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

        // ── Sync user ke DB E-Portal (hanya untuk kebutuhan JWT & log) ────────
        $user = User::firstOrCreate(
            ['email' => $uclUser->email],
            [
                'name'     => $uclUser->email,
                'password' => $uclUser->password,
                'role'     => $uclUser->role,
            ]
        );

        // Sync role & password kalau berubah di UCL
        $needsUpdate = [];
        if ($user->role     !== $uclUser->role)     $needsUpdate['role']     = $uclUser->role;
        if ($user->password !== $uclUser->password) $needsUpdate['password'] = $uclUser->password;
        if (!empty($needsUpdate)) $user->update($needsUpdate);

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
            metadata: ['ip' => $request->ip()],
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

        return ResponseBuilder::success(200, 'Login berhasil', [
            'user' => [
                'id'    => $user->id,
                'email' => $uclUser->email,
                'role'  => $uclUser->role,
                'nidn'  => $uclUser->nidn,
                'npm'   => $uclUser->npm,
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
            \Log::error($e->getTraceAsString());
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

            // Ambil role langsung dari kolom, bukan Spatie
            $role = $user->role ?? null;
            $isAdmin = in_array(strtolower($role ?? ''), ['admin', 'super-admin']);

            $userData = [
                'id'    => $user->user_id,
                'email' => $user->email,
                'name'  => $user->email,
                'role'  => $role,
                'nidn'  => $user->nidn ?? null,
                'npm'   => $user->npm  ?? null,
                'image' => null,
            ];

            // Permission & modul berdasarkan role
            if ($isAdmin) {
                $allModules = \App\Models\AppModule::orderBy('name')->get();
                $accessibleModulesData = $allModules->map(fn($mod) => [
                    'id'          => $mod->id,
                    'name'        => $mod->name,
                    'url'         => $mod->url,
                    'permissions' => [],
                ])->values();
            } else {
                $accessibleModulesData = \App\Models\AppModule::orderBy('name')->get()->map(fn($mod) => [
                    'id'          => $mod->id,
                    'name'        => $mod->name,
                    'url'         => $mod->url,
                    'permissions' => [],
                ])->values();
            }

            $userData['permissions']          = [];
            $userData['permissions_by_module'] = [];
            $userData['module_permissions']   = [];
            $userData['accessible_modules']   = $accessibleModulesData;

            return response()->json([
                'status'  => 200,
                'success' => true,
                'message' => 'User data retrieved successfully',
                'data'    => $userData
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'success' => false,
                'message' => 'Failed to retrieve user data: ' . $e->getMessage(),
                'data'    => []
            ], 500);
        }
    }

    public function sendResetLinkEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);


        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'success' => false,
                'message' => $validator->errors()->first(),
                'data' => []
            ], 400);
        }

        // generate token dan kirim email

        try {
            $status = Password::sendResetLink(
                $request->only('email')
            );

            if ($status == Password::RESET_LINK_SENT) {
                return response()->json([
                    'status' => 200,
                    'success' => true,
                    'message' => 'Password reset link sent successfully. Please check your email.',
                    'data' => []
                ], 200);
            }

            return response()->json([
                'status' => 400,
                'success' => false,
                'message' => 'Failed to send password reset link',
                'data' => []
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'success' => false,
                'message' => 'An error occurred while sending the password reset link : ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    public function resetPassword(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'success' => false,
                'message' => $validator->errors()->first(),
                'data' => []
            ], 400);
        }

        try {
            $status = Password::broker()->reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function ($user, $password) {
                    $user->password = Hash::make($password);
                    $user->setRememberToken(Str::random(60));
                    $user->save();

                    event(new PasswordReset($user));
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return response()->json([
                    'status' => 200,
                    'success' => true,
                    'message' => 'Password has been reset successfully.',
                    'data' => []
                ], 200);
            }

            return response()->json([
                'status' => 400,
                'success' => false,
                'message' => 'Failed to reset password. Please check your token and email.',
                'data' => []
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'success' => false,
                'message' => 'An error occurred while resetting the password : ' . $e->getMessage(),
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
            return redirect('http://localhost:5173/login?error=GoogleLoginFailed&msg=' . urlencode($e->getMessage()));
        }
    }

    public function tokenFromCookie(Request $request)
    {
        $token = $request->cookie('uika_sso_token');

        if (!$token) {
            return response()->json(['status' => 401, 'message' => 'No cookie found.'], 401);
        }

        try {
            $user = FacadesJWTAuth::setToken($token)->authenticate();

            if (!$user) {
                return response()->json(['status' => 401, 'message' => 'Invalid token.'], 401);
            }

            return response()->json([
                'status' => 200,
                'data'   => [
                    'uika_sso_token' => $token,
                    'user' => [
                        'id'    => $user->user_id,
                        'email' => $user->email,
                        'role'  => $user->role,
                        'nidn'  => $user->nidn,
                        'npm'   => $user->npm,
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
            // Baca token dari HttpOnly cookie, fallback ke bearer token jika cookie kosong (misal di local dev HTTP cross-site)
            $token = $request->cookie('uika_sso_token') ?: $request->bearerToken();

            if (!$token) {
                return response()->json([
                    'status' => 401,
                    'message' => 'No active session found.',
                ], 401);
            }

            // Validasi token masih valid dan dapatkan user object
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

            // Get role model details for metadata
            // $roleModel = \App\Models\Role::find($role_id);
            $roleModel = null;

            // Calculate the permissions for this user, module, and role context
            $permissions = $this->getPermissionsForContext($user, $appModule_id, $role_id);

            // Generate a scoped token for the sub-app containing the contextual permissions
            $scopedToken = FacadesJWTAuth::claims([
                'id'           => $user->public_id,
                'email'        => $user->email,
                'appModule_id' => (int) $appModule_id,
                'role_id'      => (int) $role_id,
                'role_name'    => $roleModel?->name,
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
            \Log::error($e->getTraceAsString());

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
        if (in_array($user->role, ['admin', 'super-admin'])) {
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
