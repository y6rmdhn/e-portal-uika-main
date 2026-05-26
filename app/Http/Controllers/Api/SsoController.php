<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SsoUserResource;
use App\Models\AppModule;
use App\Models\Permission;
use App\Models\SsoClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * SsoController
 *
 * Endpoint-endpoint yang dirancang khusus untuk dikonsumsi oleh sub-aplikasi
 * (SIAKAD, E-Library, Portal Keuangan, dll.) sebagai bagian dari integrasi SSO.
 *
 * Semua endpoint di sini (kecuali capabilities) membutuhkan:
 * 1. Token JWT valid (dari cookie uika_sso_token atau Authorization header)
 * 2. SSO Client credentials (X-SSO-Client-ID + X-SSO-Client-Secret header)
 */
class SsoController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // PUBLIC — Tidak butuh autentikasi
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /api/sso/capabilities
     *
     * Self-documenting endpoint yang mendeskripsikan kontrak SSO.
     * Berguna untuk developer sub-aplikasi memahami apa yang dijamin SSO.
     * Tidak membutuhkan token maupun client credentials.
     */
    public function capabilities(): JsonResponse
    {
        return response()->json([
            'status'  => 200,
            'service' => 'E-Portal UIKA SSO',
            'version' => '2.0',
            'issued_by' => config('app.url'),

            'description' => 'E-Portal SSO menyediakan autentikasi terpusat dan manajemen hak akses modul untuk ekosistem aplikasi UIKA.',

            // ── Yang DIJAMIN oleh SSO ─────────────────────────────────────────
            'sso_guarantees' => [
                'identity'            => 'Identitas user (nama, email, NIDN/NIM/NIP) terverifikasi.',
                'email_verification'  => 'Hanya user dengan email terverifikasi yang bisa login.',
                'institutional_role'  => 'Role institusional (dosen, mahasiswa, admin, staff, user).',
                'module_access'       => 'Daftar modul yang boleh diakses user.',
                'module_permissions'  => 'Permission level modul (contoh: siakad.view, siakad.input_nilai).',
                'token_validity'      => 'Validitas token JWT (expired, invalid, atau aktif).',
                'account_status'      => 'Status aktif/nonaktif akun (is_active).',
            ],

            // ── Yang BUKAN tanggung jawab SSO ─────────────────────────────────
            'app_responsibility' => [
                'contextual_roles'  => 'Role kontekstual (Kaprodi Prodi TI, Bendahara Unit X) — simpan di DB lokal.',
                'org_structure'     => 'Struktur organisasi (fakultas, prodi, unit) — kelola di app masing-masing.',
                'granular_perms'    => 'Permission granular dalam app (input_nilai, acc_krs) — kelola di app sendiri.',
                'domain_entities'   => 'Entitas bisnis (KRS, nilai, peminjaman buku) — bukan domain SSO.',
            ],

            // ── Endpoint yang Tersedia ─────────────────────────────────────────
            'endpoints' => [
                [
                    'method'      => 'POST',
                    'path'        => '/api/sso/introspect',
                    'description' => 'Validasi token + dapatkan data user & permissions. ENDPOINT UTAMA.',
                    'auth'        => 'JWT Token (cookie atau Bearer) + SSO Client Credentials',
                    'headers'     => [
                        'X-SSO-Client-ID'     => 'Client ID sub-aplikasi Anda',
                        'X-SSO-Client-Secret' => 'Client Secret sub-aplikasi Anda',
                    ],
                    'query_params' => [
                        'appModule_id' => '(required) ID modul sub-aplikasi Anda di sistem SSO',
                    ],
                ],
                [
                    'method'      => 'GET',
                    'path'        => '/api/sso/verify-access',
                    'description' => 'Cek cepat apakah user punya akses ke modul tertentu.',
                    'auth'        => 'JWT Token + SSO Client Credentials',
                    'query_params' => [
                        'appModule_id' => '(required) ID modul yang ingin dicek',
                    ],
                ],
                [
                    'method'      => 'GET',
                    'path'        => '/api/sso/capabilities',
                    'description' => 'Dokumentasi ini. Tidak butuh autentikasi.',
                    'auth'        => 'None',
                ],
            ],

            // ── Panduan Integrasi ──────────────────────────────────────────────
            'integration_guide' => 'Lihat file SSO_INTEGRATION_GUIDE.md di root proyek SSO.',

            // ── Kontak ────────────────────────────────────────────────────────
            'contact' => 'Hubungi admin E-Portal untuk registrasi SSO Client dan mendapatkan credentials.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PROTECTED — Butuh JWT + SSO Client Credentials
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * POST /api/sso/introspect
     *
     * Endpoint UTAMA untuk sub-aplikasi. Dipanggil saat user baru datang via SSO redirect.
     *
     * Sub-aplikasi kirim:
     * - Token JWT (via cookie uika_sso_token atau Authorization: Bearer)
     * - X-SSO-Client-ID + X-SSO-Client-Secret header
     * - Query param: appModule_id (ID modul sub-aplikasi di sistem SSO)
     *
     * SSO akan kembalikan:
     * - Data user lengkap (sso_id, nama, email, identitas akademik, dll.)
     * - Apakah user punya akses ke modul tersebut
     * - Daftar permission user untuk modul tersebut
     * - Metadata token
     */
    public function introspect(Request $request): JsonResponse
    {
        // ── 1. Ambil & Validasi Token ──────────────────────────────────────────
        // Mendukung cookie (uika_sso_token) maupun Authorization header
        $token = $request->cookie('uika_sso_token') ?: $request->bearerToken();

        if (!$token) {
            return response()->json([
                'status'  => 401,
                'valid'   => false,
                'message' => 'No token provided. Send JWT via Authorization: Bearer or uika_sso_token cookie.',
                'user'    => null,
                'access'  => null,
            ], 401);
        }

        // ── 2. Decode & Authenticate Token ────────────────────────────────────
        try {
            $user = JWTAuth::setToken($token)->authenticate();

            if (!$user) {
                return response()->json([
                    'status'  => 401,
                    'valid'   => false,
                    'message' => 'Token valid but user not found.',
                    'user'    => null,
                    'access'  => null,
                ], 401);
            }
        } catch (TokenExpiredException) {
            return response()->json([
                'status'  => 401,
                'valid'   => false,
                'message' => 'Token has expired. Please re-authenticate via SSO.',
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
        }

        // ── 3. Cek Status Akun ─────────────────────────────────────────────────
        if (!$user->is_active) {
            return response()->json([
                'status'  => 403,
                'valid'   => true,  // token valid, tapi akun dinonaktifkan
                'message' => 'User account is inactive.',
                'user'    => (new SsoUserResource($user))->toArray($request),
                'access'  => ['has_access' => false, 'reason' => 'account_inactive'],
            ], 403);
        }

        // ── 4. Ambil Payload & Tentukan Jenis Token (Scoped vs Global) ─────────
        $payload    = JWTAuth::setToken($token)->getPayload();
        $isScoped   = $payload->get('is_scoped') ?? false;

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
            // Fallback: Token Global lama, butuh appModule_id di query param
            $appModuleId = $request->query('appModule_id');

            if ($user->hasAnyRole(['admin', 'super-admin'])) {
                $allPermissions = Permission::all();
            } else {
                $allPermissions = $user->getAllPermissions();
            }

            $accessData = $this->resolveModuleAccess($user, $allPermissions, $appModuleId, $request);
        }

        // ── 5. Ambil SSO Client dari middleware (untuk validasi allowed_module_ids) ──
        /** @var \App\Models\SsoClient $ssoClient */
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

        // ── 6. Decode Token Payload untuk Metadata ────────────────────────────
        $expireAt   = $payload->get('exp')
            ? \Carbon\Carbon::createFromTimestamp($payload->get('exp'))->toIso8601String()
            : null;

        return response()->json([
            'status'  => 200,
            'valid'   => true,
            'message' => 'Token is valid.',

            // Data user yang distandarisasi untuk sub-aplikasi
            'user'   => (new SsoUserResource($user))->toArray($request),

            // Informasi akses user ke modul ini
            'access' => $accessData,

            // Metadata SSO
            'sso_meta' => [
                'issued_by'       => 'E-Portal UIKA',
                'token_expires_at' => $expireAt,
                'introspected_at' => now()->toIso8601String(),
                'client_name'     => $ssoClient?->name,
            ],
        ]);
    }

    /**
     * GET /api/sso/verify-access?appModule_id={id}
     *
     * Cek cepat apakah user yang sedang login punya akses ke modul tertentu.
     * Lebih ringan dari introspect — hanya kembalikan has_access + permissions.
     *
     * Cocok digunakan sebagai gate check di middleware sub-aplikasi pada setiap request,
     * dengan caching di sisi sub-aplikasi (cache 5-15 menit).
     */
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

        if (!$user->is_active) {
            return response()->json([
                'status'     => 403,
                'has_access' => false,
                'message'    => 'Account is inactive.',
                'sso_id'     => $user->public_id,
            ], 403);
        }

        $isScoped = $payload->get('is_scoped') ?? false;

        if ($isScoped) {
            $tokenAppModuleId = $payload->get('appModule_id');
            // Pastikan token memang diperuntukkan untuk appModule_id yang diminta
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
            if ($user->hasAnyRole(['admin', 'super-admin'])) {
                $allPermissions = Permission::all();
            } else {
                $allPermissions = $user->getAllPermissions();
            }

            $accessData = $this->resolveModuleAccess($user, $allPermissions, $appModuleId, $request);
        }

        return response()->json([
            'status'      => 200,
            'sso_id'      => $user->public_id,
            'has_access'  => $accessData['has_access'],
            'permissions' => $accessData['permissions'],
            'module'      => $accessData['module'],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Resolve informasi akses user ke modul tertentu.
     * Termasuk: has_access (bool), daftar permissions, dan data modul.
     *
     * @param \App\Models\User $user
     * @param \Illuminate\Support\Collection $allPermissions
     * @param int|string|null $appModuleId
     * @param Request $request
     */
    private function resolveModuleAccess($user, $allPermissions, $appModuleId, Request $request): array
    {
        // Group permissions by appModule_id
        $permissionsByModule = [];
        foreach ($allPermissions as $perm) {
            if ($perm->appModule_id) {
                $permissionsByModule[$perm->appModule_id][] = $perm->name;
            }
        }

        if (!$appModuleId) {
            // Tidak ada modul spesifik → kembalikan semua modul yang bisa diakses
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

        // Ada modul spesifik → cek akses ke modul itu
        $modulePermissions = $permissionsByModule[$appModuleId] ?? [];
        $hasAccess         = !empty($modulePermissions) || $user->hasAnyRole(['admin', 'super-admin']);

        // Ambil data modul
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
