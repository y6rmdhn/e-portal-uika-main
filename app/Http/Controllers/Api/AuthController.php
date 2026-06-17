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
        // ambil data input
        $data = $request->only('name', 'email', 'password', 'role_id');

        // validasi data input
        $validator = Validator::make($data, [
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|max:50',
            'role_id' => 'required'
        ]);

        //Kirim respons gagal jika permintaan tidak valid
        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'message' => $validator->errors()->first(),
                'data' => []
            ], 400);
        }

        // db transaction
        DB::beginTransaction();

        // Permintaan valid, buat pengguna baru.
        try {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role_id' => $data['role_id']
            ]);

            $roleMap = [1 => 'admin', 2 => 'user'];
            if (isset($roleMap[$data['role_id']])) {
                $user->assignRole($roleMap[$data['role_id']]);
            }

            event(new Registered($user));

            DB::commit();

            // kembalikan response sukses
            return response()->json([
                'status' => 201,
                'success' => true,
                'message' => 'User created successfully. Please check your email to verify your account.',
                'data' => $user
            ], 201);
        } catch (\Exception $th) {

            DB::rollBack();

            return response()->json([
                'status' => 500,
                'success' => false,
                'message' => 'System error: ' . $th->getMessage(),
                'data' => []
            ], 500);
        }
    }

    public function auth(Request $request)
    {
        // validasi input
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'message' => 'Email and password must be filled in correctly.',
                'data' => $validator->errors()
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
                'message' => "Akun ini sementara dikunci karena terlalu banyak percobaan. Coba lagi dalam {$remaining} detik.",
                'data'    => []
            ], 429);
        }

        // mengambil email dan password dari request
        $credentials = $request->only('email', 'password');

        // cek verifikasi email
        $userCheck = User::where('email', $request->email)->first();

        if ($userCheck && !$userCheck->hasVerifiedEmail()) {
            $this->loginLogService->logFailure($request, 'email_not_verified');

            return response()->json([
                'status' => 403,
                'message' => 'Login failed. Please verify your email first.',
                'data' => []
            ], 403);
        }

        try {
            if (! $token = FacadesJWTAuth::attempt($credentials)) {
                $this->loginLogService->logFailure($request, 'invalid_credentials');

                return response()->json([
                    'status' => 401,
                    'message' => 'Incorrect email or password.',
                    'data' => []
                ], 401);
            }
        } catch (JWTException $e) {
            return response()->json([
                'status' => 500,
                'message' => 'A system error occurred, unable to create a token.',
                // for debugging purpose, you can uncomment the line below to see the actual error message
                // 'message' => $e->getMessage(),
                'data' => []
            ], 500);
        }

        // Ambil data user dari JWT Auth
        $user = FacadesJWTAuth::user();

        $user->update(['last_login_at' => now()]);

        $this->activityLog->log(
            ActivityLogService::TYPE_LOGIN,
            'Login ke E-Portal',
            userId: $user->id,
            actorId: $user->id,
            metadata: ['ip' => $request->ip(), 'browser' => $request->userAgent()],
        );

        $this->loginLogService->logSuccess($user->id, $request);

        // 1. Ambil nama role menggunakan fitur bawaan Spatie
        $roleName = $user->getRoleNames()->first();
        $role = $user->roles()->first();

        // 2. Ubah object model menjadi array dan sisipkan role secara manual
        $userData = $user->makeHidden('roles')->toArray();
        $userData['role'] = $roleName;
        $userData['role_id'] = $role ? $role->id : null;

        // Set environment untuk cookie
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

        // 3. Return response menggunakan $userData yang sudah berbentuk array
        return ResponseBuilder::success(200, "Login successful", [
            'user' => $userData,
            'uika_sso_token' => $token
        ])->withCookie($cookie);
    }

    public function authTias(Request $request)
    {
        $credentials = $request->only('email', 'password');

        //valid credential
        $get = Http::post('https://api-tias.ti.ft.uika-bogor.ac.id/auth/login', [
            'email' => $request->email,
            'password' => $request->password
        ]);
        $data = json_decode($get->body(), true);

        if ($data['message'] == 'Login Success.') {

            // Request is validated
            // Crean token
            if ($data['data']['role'] == 'Admin') {
                $email = 'su-admin@gmail.com';
                $pass = 'qwe123QWE!@#';
            } else {
                $email = 'gutam.gt@gmail.com';
                $pass = 'qwe123QWE!@#';
            }

            try {

                if (! $token = JWTAuth::attempt([
                    'email' => $email,
                    'password' => $pass
                    // 'email' => $request->email,
                    // 'password' => $request->password
                ])) {
                    return response()->json([
                        'status' => 400,
                        'message' => 'Login credentials are invalid.',
                        'data' => []
                    ], 200);
                }
            } catch (JWTException $e) {
                return $credentials;
                return response()->json([
                    'status' => 200,
                    'message' => 'Could not create token.',
                    'data' => []
                ], 200);
            }

            //Token created, return with success response and jwt token
            $user = JWTAuth::user();
            return ResponseBuilder::success(200, "success", [
                'user' => $data['data'],
                'uika_sso_token' => $token,
            ]);
        } else {
            return response()->json($data, 200);
            // return ResponseBuilder::success(200, "Email tidak terdaftar", null);
        }
    }

    public function logout(Request $request)
    {
        //Request is validated, do logout
        try {
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
        }
    }

    public function refresh(Request $request)
    {

        $validator = Validator::make($request->only('token'), []);
        if ($validator->fails()) {
            return ResponseBuilder::success(200, "error", $validator->messages());
        }
        return ResponseBuilder::success(200, "success", [
            'user' => JWTAuth::user(),
            'token' => JWTAuth::refresh(),
        ]);
    }

    public function get_user(Request $request)
    {
        try {
            $user = FacadesJWTAuth::user();

            $roleName = $user->getRoleNames()->first();
            $role = $user->roles()->first();

            $userData = $user->makeHidden('roles')->toArray();
            $userData['role'] = $roleName;
            $userData['role_id'] = $role ? $role->id : null;

            if (!empty($userData['image']) && !filter_var($userData['image'], FILTER_VALIDATE_URL)) {
                $userData['image'] = asset('storage/' . $userData['image']);
            }

            // Ambil semua permission user (jika admin/super-admin, ambil semua permission di system)
            if ($user->hasAnyRole(['admin', 'super-admin'])) {
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

            // ── Accessible Modules ────────────────────────────────────────────
            // Array modul lengkap yang dapat diakses user (id, name, url, permissions).
            // Digunakan frontend E-Portal untuk tampilkan daftar app, dan sub-aplikasi
            // untuk validasi hak akses tanpa query tambahan.
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
                'message' => 'Failed to retrieve user data.',
                'data' => []
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
            $user = User::where('email', $googleUser->email)->first();

            if (!$user) {
                $pendingData = base64_encode(json_encode([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                ]));
                return redirect('http://localhost:5173/register?social_data=' . $pendingData);
            }

            $token = FacadesJWTAuth::fromUser($user);

            $isProduction = config('app.env') === 'production';
            $cookieDomain = $isProduction ? '.uika.ac.id' : null;

            $cookie = cookie(
                'uika_sso_token',
                $token,
                1440,
                '/',
                $cookieDomain,
                $isProduction,
                true,
                false,
                'Lax'
            );

            // Tidak ada data di URL sama sekali
            return redirect('http://localhost:5173/auth/google/success')
                ->withCookie($cookie);
        } catch (\Exception $e) {
            return redirect('http://localhost:5173/login?error=GoogleLoginFailed');
        }
    }

    // public function call_user(Request $request)
    // {
    //     $validator = Validator::make($request->only('token'), []);
    //     if ($validator->fails()) {
    //         return ResponseBuilder::success(200, "error", $validator->messages());
    //     }


    //     $unitId = $request->input('unit_id');
    //     $roleId = $request->input('role_id');
    //     $appModuleId = $request->input('appModule_id');

    //     $data = TxUserModulPermission::select(['appModule_id', 'role_id', 'unit_id'])
    //         ->where('user_id', JWTAuth::user()->id)
    //         ->with([
    //             'unit' => function ($q) {
    //                 $q->select('id', 'name', 'status');
    //                 // jangan lupa include 'unit_id' supaya relasi tetap bisa jalan
    //             },
    //             'role' => function ($q) {
    //                 $q->select('id', 'name');
    //                 // jangan lupa include 'role_id' supaya relasi tetap bisa jalan
    //             },
    //             'roleHasPermission',
    //             'appModul' => function ($q) {
    //                 $q->select('id', 'name', 'url');
    //                 // jangan lupa include 'app_modul_id' supaya relasi tetap bisa jalan
    //             },
    //             'appModul.permission' => function ($q) {
    //                 $q->select('id', 'appModule_id', 'name');
    //                 // jangan lupa include 'app_modul_id' supaya relasi tetap bisa jalan
    //             }
    //         ]);
    //     if ($roleId && $appModuleId && $unitId) {
    //         $data = $data->where('role_id', $roleId)
    //             ->where('appModule_id', $appModuleId)
    //             ->where('unit_id', $unitId);
    //     } else {
    //         return ResponseBuilder::success(200, "error", 'Parameter yang di butuhkan tidak sesuai / harus diisi');
    //     }

    //     $data = $data->get();

    //     return ResponseBuilder::success(200, "success", [
    //         'user' => JWTAuth::user()->only('id', 'name', 'email', 'nidn', 'nip', 'npm', 'phone', 'location', 'is_active', 'image'),
    //         // 'user_module_permission' => $data,

    //         'detail' => $data,
    //         // 'permissions' => $data->pluck('appModul')->flatten()->pluck('permission')->flatten()->unique('id')->values(),

    //         // 'role_has_permission' => null,
    //         'token_eportal' => 'Bearer ' . $request->token
    //     ]);
    // }

    public function call_user(Request $request)
    {
        $validator = Validator::make($request->only('token'), []);
        if ($validator->fails()) {
            return ResponseBuilder::success(200, "error", $validator->messages());
        }

        $roleId      = $request->input('role_id');
        $appModuleId = $request->input('appModule_id');

        if (!$roleId || !$appModuleId) {
            return ResponseBuilder::success(200, "error", 'Parameter role_id dan appModule_id harus diisi');
        }

        $user = JWTAuth::user();

        // Ambil role dari Spatie
        $roleName = $user->getRoleNames()->first() ?? '';

        $data = TxUserModulPermission::select(['appModule_id', 'role_id', 'permission_id'])
            ->where('user_id', $user->id)
            ->where('role_id', $roleId)
            ->where('appModule_id', $appModuleId)
            ->with([
                'role' => function ($q) {
                    $q->select('id', 'name');
                },
                'roleHasPermission',
                'appModul' => function ($q) {
                    $q->select('id', 'name', 'url');
                },
                'appModul.permission' => function ($q) {
                    $q->select('id', 'appModule_id', 'name');
                }
            ])
            ->get();

        $userData = $user->only('id', 'name', 'email', 'nidn', 'nip', 'npm', 'phone', 'location', 'is_active', 'image');
        $userData['role'] = $roleName; // ← tambah ini

        return ResponseBuilder::success(200, "success", [
            'user'          => $userData,
            'detail'        => $data,
            'token_eportal' => 'Bearer ' . $request->token
        ]);
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
            $roleModel = \App\Models\Role::find($role_id);

            // Calculate the permissions for this user, module, and role context
            $permissions = $this->getPermissionsForContext($user, $appModule_id, $role_id);

            // Generate a scoped token for the sub-app containing the contextual permissions
            $scopedToken = FacadesJWTAuth::claims([
                'id'           => $user->public_id,
                'email'        => $user->email,
                'appModule_id' => (int) $appModule_id,
                'role_id'      => (int) $role_id,
                'role_name'    => $roleModel?->name,
                'unit_id'      => $user->unit_id ? (int) $user->unit_id : null,
                'unit_name'    => $user->unit?->nama_unit,
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
            return response()->json([
                'status'  => 401,
                'message' => 'Session invalid or expired. Error: ' . $e->getMessage(),
            ], 401);
        }
    }

    private function getPermissionsForContext($user, $appModuleId, $roleId): array
    {
        // If user is admin/super-admin globally, grant all permissions of the module
        if ($user->hasAnyRole(['admin', 'super-admin'])) {
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
