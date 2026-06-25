<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SsoUserResource;
use App\Models\AppModule;
use App\Models\Permission;
use App\Models\SsoClient;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class SsoController extends Controller
{
    public function capabilities(): JsonResponse
    {
        return response()->json([
            'status'  => 200,
            'service' => 'E-Portal UIKA SSO',
            'version' => '2.0',
            'issued_by' => config('app.url'),
            'description' => 'E-Portal SSO menyediakan autentikasi terpusat dan manajemen hak akses modul untuk ekosistem aplikasi UIKA.',
            'sso_guarantees' => [
                'identity'            => 'Identitas user (nama, email, NIDN/NIM/NIP) terverifikasi.',
                'email_verification'  => 'Hanya user dengan email terverifikasi yang bisa login.',
                'institutional_role'  => 'Role institusional (dosen, mahasiswa, admin, staff, user).',
                'module_access'       => 'Daftar modul yang boleh diakses user.',
                'module_permissions'  => 'Permission level modul (contoh: siakad.view, siakad.input_nilai).',
                'token_validity'      => 'Validitas token JWT (expired, invalid, atau aktif).',
                'account_status'      => 'Status aktif/nonaktif akun (is_active).',
            ],
            'app_responsibility' => [
                'contextual_roles'  => 'Role kontekstual (Kaprodi Prodi TI, Bendahara Unit X) — simpan di DB lokal.',
                'org_structure'     => 'Struktur organisasi (fakultas, prodi, unit) — kelola di app masing-masing.',
                'granular_perms'    => 'Permission granular dalam app (input_nilai, acc_krs) — kelola di app sendiri.',
                'domain_entities'   => 'Entitas bisnis (KRS, nilai, peminjaman buku) — bukan domain SSO.',
            ],
        ]);
    }

    public function introspect(Request $request): JsonResponse
    {
        $t1 = microtime(true);

        $token = $request->cookie('uika_sso_token') ?: $request->bearerToken();

        if (!$token) {
            return response()->json([
                'status'  => 401,
                'valid'   => false,
                'message' => 'No token provided.',
                'user'    => null,
                'access'  => null,
            ], 401);
        }

        try {
            $payload = JWTAuth::setToken($token)->getPayload();
            $userId = $payload->get('sub');

            // Fetch central User model using Eloquent
            $user = User::where('user_id', $userId)->first();
            if (!$user) {
                $user = (object) [
                    'user_id'    => $userId,
                    'email'      => $payload->get('email'),
                    'role'       => $payload->get('role'),
                    'nidn'       => $payload->get('nidn'),
                    'npm'        => $payload->get('npm'),
                    'isverified' => true,
                ];
            }
        } catch (TokenExpiredException) {
            return response()->json([
                'status'  => 401,
                'valid'   => false,
                'message' => 'Token has expired.',
                'user'    => null,
                'access'  => null,
            ], 401);
        } catch (TokenInvalidException) {
            return response()->json([
                'status'  => 401,
                'valid'   => false,
                'message' => 'Token is invalid.',
                'user'    => null,
                'access'  => null,
            ], 401);
        } catch (JWTException) {
            return response()->json([
                'status'  => 401,
                'valid'   => false,
                'message' => 'Could not process token.',
                'user'    => null,
                'access'  => null,
            ], 401);
        } catch (\Exception $e) {
            \Log::error('Introspect error: ' . $e->getMessage());
            return response()->json([
                'status'  => 500,
                'valid'   => false,
                'message' => $e->getMessage(),
            ], 500);
        }

        $isScoped = $payload->get('is_scoped') ?? false;

        if ($isScoped) {
            $appModuleId = $payload->get('appModule_id');
            $roleId      = $payload->get('role_id');
            $roleName    = $payload->get('role_name');
            $unitId      = $payload->get('unit_id');
            $unitName    = $payload->get('unit_name');
            $permissions = $payload->get('permissions') ?? [];

            $module = AppModule::find($appModuleId);

            $accessData = [
                'has_access'  => true,
                'module'      => $module ? [
                    'id'   => $module->id,
                    'name' => $module->name,
                    'url'  => $module->url,
                ] : null,
                'role_id'     => $roleId,
                'role_name'   => $roleName,
                'unit_id'     => $unitId,
                'unit_name'   => $unitName,
                'permissions' => $permissions,
            ];
        } else {
            $appModuleId = $request->query('appModule_id');
            $accessData  = [];
        }

        $ssoClient = $request->attributes->get('sso_client');
        if ($appModuleId && $ssoClient && !$ssoClient->canAccessModule((int) $appModuleId)) {
            return response()->json([
                'status'  => 403,
                'valid'   => true,
                'message' => 'This SSO client is not authorized to introspect module ' . $appModuleId . '.',
                'user'    => null,
                'access'  => null,
            ], 403);
        }

        $expireAt = $payload->get('exp')
            ? \Carbon\Carbon::createFromTimestamp($payload->get('exp'))->toIso8601String()
            : null;

        return response()->json([
            'status'  => 200,
            'valid'   => true,
            'message' => 'Token is valid.',
            'user'    => (new SsoUserResource($user))->toArray($request),
            'access'  => $accessData,
            'sso_meta' => [
                'issued_by'        => 'E-Portal UIKA',
                'token_expires_at' => $expireAt,
                'introspected_at'  => now()->toIso8601String(),
                'client_name'      => $ssoClient?->name,
            ],
        ]);
    }

    public function verifyAccess(Request $request): JsonResponse
    {
        $appModuleId = $request->query('appModule_id');

        if (!$appModuleId) {
            return response()->json([
                'status'     => 422,
                'has_access' => false,
                'message'    => 'Parameter appModule_id is required.',
            ], 422);
        }

        $token = $request->cookie('uika_sso_token') ?: $request->bearerToken();

        if (!$token) {
            return response()->json([
                'status'     => 401,
                'has_access' => false,
                'message'    => 'No token provided.',
            ], 401);
        }

        try {
            $user = JWTAuth::setToken($token)->authenticate();
            $payload = JWTAuth::setToken($token)->getPayload();
        } catch (JWTException) {
            return response()->json([
                'status'     => 401,
                'has_access' => false,
                'message'    => 'Invalid or expired token.',
            ], 401);
        }

        $isverified = $user->isverified ?? true;
        if (!$isverified) {
            return response()->json([
                'status'     => 403,
                'has_access' => false,
                'message'    => 'Account is inactive.',
                'sso_id'     => $user->user_id,
            ], 403);
        }

        $isScoped = $payload->get('is_scoped') ?? false;

        if ($isScoped) {
            $tokenAppModuleId = $payload->get('appModule_id');
            if ((int)$tokenAppModuleId !== (int)$appModuleId) {
                return response()->json([
                    'status'     => 403,
                    'has_access' => false,
                    'message'    => 'Token is scoped to module ' . $tokenAppModuleId . ', but requested module ' . $appModuleId,
                ], 403);
            }

            $permissions = $payload->get('permissions') ?? [];
            $module = AppModule::find($appModuleId);

            $accessData = [
                'has_access'  => true,
                'module'      => $module ? [
                    'id'   => $module->id,
                    'name' => $module->name,
                    'url'  => $module->url,
                ] : null,
                'permissions' => $permissions,
            ];
        } else {
            $isGlobalAdmin = in_array(strtolower($user->role ?? ''), ['admin', 'super-admin']) || $user->hasAnyRole(['admin', 'super-admin']);
            if ($isGlobalAdmin) {
                $allPermissions = Permission::all();
            } else {
                $allPermissions = $user->getAllPermissions();
            }

            $accessData = $this->resolveModuleAccess($user, $allPermissions, $appModuleId, $request);
        }

        return response()->json([
            'status'      => 200,
            'sso_id'      => $user->user_id,
            'has_access'  => $accessData['has_access'],
            'permissions' => $accessData['permissions'],
            'module'      => $accessData['module'],
        ]);
    }

    private function resolveModuleAccess($user, $allPermissions, $appModuleId, Request $request): array
    {
        $permissionsByModule = [];
        foreach ($allPermissions as $perm) {
            if ($perm->appModule_id) {
                $permissionsByModule[$perm->appModule_id][] = $perm->name;
            }
        }

        if (!$appModuleId) {
            $accessibleModuleIds = array_keys($permissionsByModule);
            $accessibleModules   = AppModule::whereIn('id', $accessibleModuleIds)
                ->orderBy('name')
                ->get()
                ->map(fn($mod) => [
                    'id'          => $mod->id,
                    'name'        => $mod->name,
                    'url'         => $mod->url,
                    'permissions' => $permissionsByModule[$mod->id] ?? [],
                ]);

            return [
                'has_access'        => true,
                'module'            => null,
                'permissions'       => $allPermissions->pluck('name')->toArray(),
                'accessible_modules' => $accessibleModules,
            ];
        }

        $modulePermissions = $permissionsByModule[$appModuleId] ?? [];
        $isGlobalAdmin = in_array(strtolower($user->role ?? ''), ['admin', 'super-admin']) || $user->hasAnyRole(['admin', 'super-admin']);
        $hasAccess         = !empty($modulePermissions) || $isGlobalAdmin;

        $module = AppModule::find($appModuleId);

        return [
            'has_access'  => $hasAccess,
            'module'      => $module ? [
                'id'   => $module->id,
                'name' => $module->name,
                'url'  => $module->url,
            ] : null,
            'permissions' => $modulePermissions,
        ];
    }
}
