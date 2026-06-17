# SSO RBAC — Laravel Implementation Notes

> Tech stack: Laravel 11, JWT (tymon/jwt-auth atau lumen-jwt), MySQL/PostgreSQL
> Dokumen ini adalah instruksi untuk AI agent. Ikuti urutan implementasi sesuai section.

---

## 1. Database Schema

Buat migrations dalam urutan berikut (perhatikan foreign key dependency).

### 1.1 `users`
```php
Schema::create('users', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('nip')->unique()->nullable();       // nomor induk pegawai
    $table->string('username')->unique();
    $table->string('password');
    $table->string('full_name');
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->softDeletes();
});
```

### 1.2 `roles`
```php
Schema::create('roles', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('name')->unique();                  // e.g. "dosen", "kaprodi"
    $table->string('display_name');
    $table->text('description')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

### 1.3 `permissions`
```php
Schema::create('permissions', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('name')->unique();                  // e.g. "course:read"
    $table->string('display_name');
    $table->string('resource');                        // e.g. "course"
    $table->string('action');                          // e.g. "read"
    $table->text('description')->nullable();
    $table->timestamps();
});
```

### 1.4 `app_modules`
```php
Schema::create('app_modules', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('name')->unique();                  // e.g. "akademik-service"
    $table->string('display_name');
    $table->string('base_url')->nullable();
    $table->string('client_id')->unique();
    $table->string('client_secret');                   // hashed via bcrypt/sha256
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

### 1.5 `user_roles` (pivot)
```php
Schema::create('user_roles', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('user_id');
    $table->uuid('role_id');
    $table->timestamp('assigned_at')->useCurrent();
    $table->uuid('assigned_by')->nullable();

    $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
    $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
    $table->unique(['user_id', 'role_id']);
});
```

### 1.6 `role_permissions` (pivot)
```php
Schema::create('role_permissions', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('role_id');
    $table->uuid('permission_id');

    $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
    $table->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
    $table->unique(['role_id', 'permission_id']);
});
```

### 1.7 `app_module_permissions` (pivot)
```php
Schema::create('app_module_permissions', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('app_module_id');
    $table->uuid('permission_id');

    $table->foreign('app_module_id')->references('id')->on('app_modules')->cascadeOnDelete();
    $table->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
    $table->unique(['app_module_id', 'permission_id']);
});
```

### 1.8 `refresh_tokens` (untuk token revocation)
```php
Schema::create('refresh_tokens', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('user_id');
    $table->string('jti')->unique();                   // JWT ID
    $table->string('token_hash');                      // sha256 dari refresh token
    $table->timestamp('expires_at');
    $table->boolean('is_revoked')->default(false);
    $table->string('revoked_reason')->nullable();
    $table->timestamps();

    $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
    $table->index(['jti', 'is_revoked']);
});
```

### 1.9 `token_blacklist` (untuk JWT SSO yang di-logout/revoke)
```php
Schema::create('token_blacklists', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('jti')->unique();
    $table->uuid('user_id');
    $table->timestamp('expires_at');
    $table->string('reason')->nullable();
    $table->timestamps();

    $table->index(['jti', 'expires_at']);
});
```

---

## 2. Eloquent Models

### 2.1 `User`
```php
// app/Models/User.php
class User extends Authenticatable implements JWTSubject
{
    use HasUuids, SoftDeletes;

    protected $fillable = ['nip', 'username', 'password', 'full_name', 'is_active'];
    protected $hidden   = ['password'];
    protected $casts    = ['is_active' => 'boolean'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
                    ->withPivot('assigned_at', 'assigned_by')
                    ->withTimestamps();
    }

    // Ambil semua permissions dari semua roles user ini (union)
    public function allPermissions(): Collection
    {
        return $this->roles()
                    ->where('roles.is_active', true)
                    ->with('permissions')
                    ->get()
                    ->flatMap(fn($role) => $role->permissions)
                    ->unique('id');
    }

    // Implementasi JWTSubject
    public function getJWTIdentifier(): mixed     { return $this->getKey(); }
    public function getJWTCustomClaims(): array   { return []; }
}
```

### 2.2 `Role`
```php
// app/Models/Role.php
class Role extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'display_name', 'description', 'is_active'];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles');
    }
}
```

### 2.3 `Permission`
```php
// app/Models/Permission.php
class Permission extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'display_name', 'resource', 'action', 'description'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }

    public function appModules(): BelongsToMany
    {
        return $this->belongsToMany(AppModule::class, 'app_module_permissions');
    }
}
```

### 2.4 `AppModule`
```php
// app/Models/AppModule.php
class AppModule extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'display_name', 'base_url', 'client_id', 'client_secret', 'is_active'];
    protected $hidden   = ['client_secret'];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'app_module_permissions');
    }
}
```

---

## 3. Services

### 3.1 `TokenService` — inti logika JWT & intersection
```php
// app/Services/TokenService.php
class TokenService
{
    /**
     * Buat SSO token setelah login berhasil.
     * Berisi roles dan accessible_modules, BELUM ada permissions detail.
     */
    public function createSsoToken(User $user): array
    {
        $roles = $user->roles()->where('is_active', true)->pluck('name')->toArray();

        // Cari modul mana saja yang accessible oleh role user ini
        $accessibleModules = AppModule::where('is_active', true)
            ->whereHas('permissions', function ($q) use ($user) {
                $q->whereHas('roles', function ($q2) use ($user) {
                    $q2->whereIn('roles.id', $user->roles()->pluck('roles.id'));
                });
            })
            ->pluck('name')
            ->toArray();

        $jti = 'tok_sso_' . Str::random(10);

        $customClaims = [
            'iss'                => config('app.url'),
            'jti'                => $jti,
            'aud'                => 'sso-portal',
            'roles'              => $roles,
            'accessible_modules' => $accessibleModules,
            'token_type'         => 'sso',
        ];

        $token = JWTAuth::customClaims($customClaims)->fromUser($user);

        return [
            'token_type'    => 'Bearer',
            'access_token'  => $token,
            'refresh_token' => $this->createRefreshToken($user, $jti),
            'expires_in'    => config('jwt.ttl') * 60,
            'payload'       => [
                'iss'                => config('app.url'),
                'sub'                => $user->id,
                'aud'                => 'sso-portal',
                'jti'                => $jti,
                'roles'              => $roles,
                'accessible_modules' => $accessibleModules,
            ],
        ];
    }

    /**
     * Buat module token — intersection rolePermissions ∩ appModulePermissions.
     * Ini adalah fungsi kunci dari seluruh sistem RBAC.
     */
    public function createModuleToken(User $user, string $moduleName): array
    {
        $module = AppModule::where('name', $moduleName)
                           ->where('is_active', true)
                           ->firstOrFail();

        // Step 1: Ambil semua permission IDs yang dimiliki module ini
        $modulePermissionIds = $module->permissions()->pluck('permissions.id')->toArray();

        // Step 2: Ambil semua permission IDs dari semua roles aktif user (union)
        $userPermissionIds = $user->roles()
            ->where('roles.is_active', true)
            ->with('permissions')
            ->get()
            ->flatMap(fn($role) => $role->permissions->pluck('id'))
            ->unique()
            ->toArray();

        // Step 3: Intersection
        $intersectedIds = array_intersect($userPermissionIds, $modulePermissionIds);

        if (empty($intersectedIds)) {
            throw new AccessDeniedHttpException(
                "No roles authorized for module: {$moduleName}"
            );
        }

        // Step 4: Ambil nama permissions hasil intersection
        $permissions = Permission::whereIn('id', $intersectedIds)
                                  ->pluck('name')
                                  ->toArray();

        // Step 5: Ambil hanya roles yang relevan dengan module ini
        $relevantRoles = $user->roles()
            ->where('roles.is_active', true)
            ->whereHas('permissions', function ($q) use ($modulePermissionIds) {
                $q->whereIn('permissions.id', $modulePermissionIds);
            })
            ->pluck('roles.name')
            ->toArray();

        $jti = 'tok_' . Str::slug($moduleName, '_') . '_' . Str::random(8);

        $customClaims = [
            'iss'        => config('app.url'),
            'jti'        => $jti,
            'aud'        => $moduleName,
            'roles'      => $relevantRoles,
            'module'     => $moduleName,
            'permissions'=> $permissions,
            'token_type' => 'module',
        ];

        $token = JWTAuth::customClaims($customClaims)->fromUser($user);

        return [
            'token_type'  => 'Bearer',
            'access_token'=> $token,
            'expires_in'  => config('jwt.module_ttl', 3600),
            'payload'     => [
                'iss'        => config('app.url'),
                'sub'        => $user->id,
                'aud'        => $moduleName,
                'jti'        => $jti,
                'roles'      => $relevantRoles,
                'module'     => $moduleName,
                'permissions'=> $permissions,
            ],
        ];
    }

    private function createRefreshToken(User $user, string $ssoJti): string
    {
        $raw  = Str::random(64);
        $hash = hash('sha256', $raw);

        RefreshToken::create([
            'user_id'     => $user->id,
            'jti'         => $ssoJti,
            'token_hash'  => $hash,
            'expires_at'  => now()->addDays(config('jwt.refresh_ttl', 30)),
        ]);

        return $raw;
    }
}
```

### 3.2 `AuthService`
```php
// app/Services/AuthService.php
class AuthService
{
    public function __construct(private TokenService $tokenService) {}

    public function login(array $credentials): array
    {
        $user = User::where('username', $credentials['username'])
                    ->where('is_active', true)
                    ->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw new UnauthorizedHttpException('', 'Invalid credentials');
        }

        return $this->tokenService->createSsoToken($user);
    }

    public function refreshToken(string $refreshTokenRaw): array
    {
        $hash   = hash('sha256', $refreshTokenRaw);
        $record = RefreshToken::where('token_hash', $hash)
                              ->where('is_revoked', false)
                              ->where('expires_at', '>', now())
                              ->firstOrFail();

        $user = User::findOrFail($record->user_id);

        // Revoke old refresh token (rotation)
        $record->update(['is_revoked' => true, 'revoked_reason' => 'rotated']);

        return $this->tokenService->createSsoToken($user);
    }

    public function logout(string $jti): void
    {
        $payload = JWTAuth::getPayload();

        TokenBlacklist::create([
            'jti'       => $jti,
            'user_id'   => auth()->id(),
            'expires_at'=> now()->addMinutes(config('jwt.ttl')),
            'reason'    => 'logout',
        ]);

        RefreshToken::where('jti', $jti)->update([
            'is_revoked'    => true,
            'revoked_reason'=> 'logout',
        ]);

        JWTAuth::invalidate(JWTAuth::getToken());
    }
}
```

---

## 4. Controllers

### 4.1 `AuthController`
```php
// app/Http/Controllers/Api/AuthController.php
class AuthController extends Controller
{
    public function __construct(private AuthService $authService) {}

    // POST /api/auth/login
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());
        return response()->json($result, 200);
    }

    // POST /api/auth/refresh
    public function refresh(Request $request): JsonResponse
    {
        $request->validate(['refresh_token' => 'required|string']);
        $result = $this->authService->refreshToken($request->refresh_token);
        return response()->json($result, 200);
    }

    // POST /api/auth/logout
    public function logout(Request $request): JsonResponse
    {
        $payload = JWTAuth::parseToken()->getPayload();
        $this->authService->logout($payload->get('jti'));
        return response()->json(['message' => 'Logged out successfully'], 200);
    }

    // GET /api/auth/me
    public function me(): JsonResponse
    {
        $user = auth()->user();
        return response()->json([
            'id'       => $user->id,
            'username' => $user->username,
            'name'     => $user->full_name,
            'roles'    => $user->roles()->pluck('name'),
        ]);
    }
}
```

### 4.2 `ModuleTokenController`
```php
// app/Http/Controllers/Api/ModuleTokenController.php
class ModuleTokenController extends Controller
{
    public function __construct(private TokenService $tokenService) {}

    // POST /api/auth/token/module
    public function issue(ModuleTokenRequest $request): JsonResponse
    {
        $user   = auth()->user();
        $module = $request->validated('module');

        $result = $this->tokenService->createModuleToken($user, $module);
        return response()->json($result, 200);
    }
}
```

### 4.3 `Admin\RoleController`
```php
// app/Http/Controllers/Api/Admin/RoleController.php
class RoleController extends Controller
{
    // GET /api/admin/roles
    public function index(): JsonResponse
    {
        return response()->json(Role::with('permissions')->paginate(20));
    }

    // POST /api/admin/roles
    public function store(RoleRequest $request): JsonResponse
    {
        $role = Role::create($request->validated());
        return response()->json($role, 201);
    }

    // PUT /api/admin/roles/{role}/permissions
    // Assign atau sync permissions ke role
    public function syncPermissions(Role $role, SyncPermissionsRequest $request): JsonResponse
    {
        $role->permissions()->sync($request->validated('permission_ids'));
        return response()->json([
            'message'     => 'Permissions synced',
            'role'        => $role->name,
            'permissions' => $role->permissions()->pluck('name'),
        ]);
    }

    // DELETE /api/admin/roles/{role}
    public function destroy(Role $role): JsonResponse
    {
        $role->delete();
        return response()->json(['message' => 'Role deleted']);
    }
}
```

### 4.4 `Admin\UserRoleController`
```php
// app/Http/Controllers/Api/Admin/UserRoleController.php
class UserRoleController extends Controller
{
    // GET /api/admin/users/{user}/roles
    public function index(User $user): JsonResponse
    {
        return response()->json($user->roles()->with('permissions')->get());
    }

    // POST /api/admin/users/{user}/roles
    // Assign role ke user
    public function assign(User $user, AssignRoleRequest $request): JsonResponse
    {
        $user->roles()->syncWithoutDetaching([
            $request->role_id => ['assigned_by' => auth()->id()]
        ]);
        return response()->json(['message' => 'Role assigned']);
    }

    // DELETE /api/admin/users/{user}/roles/{role}
    public function revoke(User $user, Role $role): JsonResponse
    {
        $user->roles()->detach($role->id);
        return response()->json(['message' => 'Role revoked']);
    }
}
```

### 4.5 `Admin\AppModuleController`
```php
// app/Http/Controllers/Api/Admin/AppModuleController.php
class AppModuleController extends Controller
{
    // GET /api/admin/modules
    public function index(): JsonResponse
    {
        return response()->json(AppModule::with('permissions')->paginate(20));
    }

    // POST /api/admin/modules
    public function store(AppModuleRequest $request): JsonResponse
    {
        $clientSecret = Str::random(64);

        $module = AppModule::create(array_merge($request->validated(), [
            'client_id'     => 'cli_' . Str::random(16),
            'client_secret' => bcrypt($clientSecret),
        ]));

        // Return raw secret SEKALI ini saja — tidak disimpan plain text
        return response()->json([
            'module'        => $module->makeVisible(['client_id']),
            'client_secret' => $clientSecret,
            'warning'       => 'Store this secret securely. It will not be shown again.',
        ], 201);
    }

    // PUT /api/admin/modules/{module}/permissions
    // Daftarkan permission apa saja yang ada di module ini
    public function syncPermissions(AppModule $module, SyncPermissionsRequest $request): JsonResponse
    {
        $module->permissions()->sync($request->validated('permission_ids'));
        return response()->json([
            'message'     => 'Module permissions updated',
            'module'      => $module->name,
            'permissions' => $module->permissions()->pluck('name'),
        ]);
    }
}
```

---

## 5. Middleware

### 5.1 `CheckPermission` — dipakai di service lain untuk verifikasi token modul
```php
// app/Http/Middleware/CheckPermission.php
class CheckPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $payload = JWTAuth::parseToken()->getPayload();

        // Pastikan token ini memang untuk service ini
        $expectedAudience = config('app.module_name'); // set di .env tiap service
        if ($payload->get('aud') !== $expectedAudience) {
            return response()->json(['error' => 'Token audience mismatch'], 403);
        }

        $tokenPermissions = $payload->get('permissions', []);

        foreach ($permissions as $permission) {
            if (!in_array($permission, $tokenPermissions)) {
                return response()->json([
                    'error'    => 'Forbidden',
                    'required' => $permission,
                ], 403);
            }
        }

        return $next($request);
    }
}
```

### 5.2 `CheckTokenBlacklist`
```php
// app/Http/Middleware/CheckTokenBlacklist.php
class CheckTokenBlacklist
{
    public function handle(Request $request, Closure $next): Response
    {
        $payload = JWTAuth::parseToken()->getPayload();
        $jti     = $payload->get('jti');

        if (TokenBlacklist::where('jti', $jti)->exists()) {
            return response()->json(['error' => 'Token has been revoked'], 401);
        }

        return $next($request);
    }
}
```

---

## 6. Routes

```php
// routes/api.php

Route::prefix('auth')->group(function () {

    // Public — tidak perlu token
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    // Protected — perlu SSO token
    Route::middleware(['auth:api', 'check.blacklist'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/token/module', [ModuleTokenController::class, 'issue']);
    });
});

// Admin routes — perlu SSO token + permission admin
Route::prefix('admin')
     ->middleware(['auth:api', 'check.blacklist', 'permission:admin:access'])
     ->group(function () {

    // Roles
    Route::get('/roles', [Admin\RoleController::class, 'index']);
    Route::post('/roles', [Admin\RoleController::class, 'store']);
    Route::put('/roles/{role}', [Admin\RoleController::class, 'update']);
    Route::delete('/roles/{role}', [Admin\RoleController::class, 'destroy']);
    Route::put('/roles/{role}/permissions', [Admin\RoleController::class, 'syncPermissions']);

    // Permissions master
    Route::apiResource('/permissions', Admin\PermissionController::class);

    // User-role assignment
    Route::get('/users/{user}/roles', [Admin\UserRoleController::class, 'index']);
    Route::post('/users/{user}/roles', [Admin\UserRoleController::class, 'assign']);
    Route::delete('/users/{user}/roles/{role}', [Admin\UserRoleController::class, 'revoke']);

    // App modules
    Route::get('/modules', [Admin\AppModuleController::class, 'index']);
    Route::post('/modules', [Admin\AppModuleController::class, 'store']);
    Route::put('/modules/{module}', [Admin\AppModuleController::class, 'update']);
    Route::put('/modules/{module}/permissions', [Admin\AppModuleController::class, 'syncPermissions']);
    Route::delete('/modules/{module}', [Admin\AppModuleController::class, 'destroy']);
});
```

---

## 7. Form Requests (Validation)

```php
// LoginRequest.php
public function rules(): array
{
    return [
        'username' => ['required', 'string'],
        'password' => ['required', 'string'],
    ];
}

// ModuleTokenRequest.php
public function rules(): array
{
    return [
        'module' => ['required', 'string', 'exists:app_modules,name'],
    ];
}

// AssignRoleRequest.php
public function rules(): array
{
    return [
        'role_id' => ['required', 'uuid', 'exists:roles,id'],
    ];
}

// SyncPermissionsRequest.php
public function rules(): array
{
    return [
        'permission_ids'   => ['required', 'array'],
        'permission_ids.*' => ['uuid', 'exists:permissions,id'],
    ];
}
```

---

## 8. Config & Environment

```env
# .env — SSO Portal
APP_URL=https://sso.univ.ac.id
APP_MODULE_NAME=sso-portal

JWT_SECRET=<generate: php artisan jwt:secret>
JWT_TTL=15                # SSO token: 15 menit
JWT_REFRESH_TTL=20160     # Refresh token: 14 hari
JWT_MODULE_TTL=60         # Module token: 60 menit

# .env — tiap service (e.g. akademik-service)
APP_MODULE_NAME=akademik-service
SSO_PUBLIC_KEY=<copy public key dari SSO jika pakai RS256>
```

```php
// config/jwt.php — tambahkan
'module_ttl' => env('JWT_MODULE_TTL', 60), // menit
```

---

## 9. File & Folder Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── AuthController.php
│   │       ├── ModuleTokenController.php
│   │       └── Admin/
│   │           ├── RoleController.php
│   │           ├── PermissionController.php
│   │           ├── UserRoleController.php
│   │           └── AppModuleController.php
│   ├── Middleware/
│   │   ├── CheckPermission.php
│   │   └── CheckTokenBlacklist.php
│   └── Requests/
│       ├── LoginRequest.php
│       ├── ModuleTokenRequest.php
│       ├── AssignRoleRequest.php
│       └── SyncPermissionsRequest.php
├── Models/
│   ├── User.php
│   ├── Role.php
│   ├── Permission.php
│   ├── AppModule.php
│   ├── RefreshToken.php
│   └── TokenBlacklist.php
└── Services/
    ├── AuthService.php
    └── TokenService.php

database/
└── migrations/
    ├── xxxx_create_users_table.php
    ├── xxxx_create_roles_table.php
    ├── xxxx_create_permissions_table.php
    ├── xxxx_create_app_modules_table.php
    ├── xxxx_create_user_roles_table.php
    ├── xxxx_create_role_permissions_table.php
    ├── xxxx_create_app_module_permissions_table.php
    ├── xxxx_create_refresh_tokens_table.php
    └── xxxx_create_token_blacklists_table.php
```

---

## 10. Urutan Implementasi (untuk AI Agent)

```
1. Buat semua migrations (section 1) → php artisan migrate
2. Buat semua Models (section 2) dengan relasi lengkap
3. Buat Services: TokenService dulu, baru AuthService (section 3)
4. Buat Form Requests (section 7)
5. Buat Controllers (section 4) — inject service via constructor
6. Registrasikan Middleware di bootstrap/app.php (section 5)
7. Daftarkan Routes di routes/api.php (section 6)
8. Set environment variables (section 8)
9. Jalankan: php artisan jwt:secret
10. Seed data awal: permissions master, roles dasar, satu admin user
```

---

## 11. Catatan Keamanan

- `client_secret` di `app_modules` wajib di-hash dengan `bcrypt` atau `SHA-256`. Jangan simpan plain text.
- Refresh token disimpan sebagai hash `SHA-256`, bukan nilai aslinya.
- Selalu validasi `aud` (audience) di middleware service penerima — token akademik tidak boleh diterima jurnal-service.
- `token_blacklist` perlu dijob-cleanup rutin untuk hapus entry yang sudah `expires_at` lewat.
- Gunakan RS256 (asymmetric) jika service-service berjalan di server berbeda — SSO pegang private key, tiap service hanya perlu public key untuk verifikasi.
- Tambahkan rate limiting di endpoint `/auth/login` untuk mencegah brute force.
