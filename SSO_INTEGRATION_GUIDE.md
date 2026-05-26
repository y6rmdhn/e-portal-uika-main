# 📡 SSO Integration Guide — E-Portal UIKA

> **Untuk:** Developer sub-aplikasi yang ingin terintegrasi dengan SSO E-Portal UIKA  
> **Versi SSO:** 2.0  
> **Terakhir diperbarui:** Mei 2026

---

## Daftar Isi

1. [Konsep Dasar](#1-konsep-dasar)
2. [Registrasi SSO Client](#2-registrasi-sso-client)
3. [Endpoint yang Tersedia](#3-endpoint-yang-tersedia)
4. [Alur Integrasi Step-by-Step](#4-alur-integrasi-step-by-step)
5. [Skema Database Sub-Aplikasi](#5-skema-database-sub-aplikasi)
6. [Contoh Middleware Laravel](#6-contoh-middleware-laravel)
7. [Contoh Middleware Express.js (Node)](#7-contoh-middleware-expressjs-node)
8. [Contoh Response Lengkap](#8-contoh-response-lengkap)
9. [Best Practices RBAC Kontekstual](#9-best-practices-rbac-kontekstual)
10. [FAQ](#10-faq)

---

## 1. Konsep Dasar

### Pembagian Tanggung Jawab

| Aspek | SSO E-Portal | Aplikasi Anda |
|---|---|---|
| **Autentikasi** | ✅ Login, logout, token JWT | ❌ |
| **Identitas user** | ✅ Nama, email, NIDN/NIM/NIP | ❌ |
| **Role institusional** | ✅ `dosen`, `mahasiswa`, `admin` | ❌ |
| **Hak akses modul** | ✅ Boleh buka app A, B, C | ❌ |
| **Role kontekstual** | ❌ | ✅ Kaprodi Prodi TI, Bendahara Unit X |
| **Struktur organisasi** | ❌ | ✅ Fakultas, Prodi, Unit |
| **Permission granular** | ❌ | ✅ input_nilai, acc_krs, lihat_laporan |

### Yang Dijamin SSO

Setiap token valid dari SSO menjamin:
- ✅ User benar-benar ada dan email-nya terverifikasi
- ✅ Akun user aktif (`is_active: true`)
- ✅ User punya hak akses ke modul Anda
- ✅ Identitas akademik (NIDN/NIM/NIP) valid

---

## 2. Registrasi SSO Client

Hubungi admin E-Portal untuk mendaftarkan aplikasi Anda. Anda akan mendapat:

```
SSO_CLIENT_ID=xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
SSO_CLIENT_SECRET=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

Simpan di `.env` aplikasi Anda:

```env
# .env sub-aplikasi
SSO_BASE_URL=https://sso.uika-bogor.ac.id
SSO_CLIENT_ID=xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
SSO_CLIENT_SECRET=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
SSO_APP_MODULE_ID=4   # ID modul Anda di sistem SSO (tanyakan ke admin)
```

---

## 3. Endpoint yang Tersedia

### Base URL
```
Production : https://sso.uika-bogor.ac.id/api
Development: http://localhost:8000/api
```

---

### `GET /api/sso/capabilities`
**Tidak butuh autentikasi**

Deskripsi kontrak SSO dalam format JSON. Cocok untuk pengecekan awal dan dokumentasi.

```bash
curl https://sso.uika-bogor.ac.id/api/sso/capabilities
```

---

### `POST /api/sso/introspect` ⭐ ENDPOINT UTAMA
**Headers wajib:** `X-SSO-Client-ID` + `X-SSO-Client-Secret`

Dipanggil saat user baru datang via SSO redirect. Validasi token + dapatkan semua data yang dibutuhkan.

```bash
curl -X POST https://sso.uika-bogor.ac.id/api/sso/introspect \
  -H "X-SSO-Client-ID: $SSO_CLIENT_ID" \
  -H "X-SSO-Client-Secret: $SSO_CLIENT_SECRET" \
  -H "Authorization: Bearer {jwt_token}" \
  -G \
  --data-urlencode "appModule_id=$SSO_APP_MODULE_ID"
```

**Query Params:**
| Param | Wajib | Keterangan |
|---|---|---|
| `appModule_id` | Opsional | ID modul Anda di SSO. Jika diisi, response akan include permissions untuk modul ini. |

---

### `GET /api/sso/verify-access`
**Headers wajib:** `X-SSO-Client-ID` + `X-SSO-Client-Secret` + Bearer Token

Cek cepat akses user ke modul Anda. Gunakan ini untuk gate check di setiap request (dengan cache lokal).

```bash
curl https://sso.uika-bogor.ac.id/api/sso/verify-access?appModule_id=4 \
  -H "X-SSO-Client-ID: $SSO_CLIENT_ID" \
  -H "X-SSO-Client-Secret: $SSO_CLIENT_SECRET" \
  -H "Authorization: Bearer {jwt_token}"
```

---

## 4. Alur Integrasi Step-by-Step

### Skenario: User Mengakses Aplikasi Anda via SSO

```
┌─────────────────────────────────────────────────────────────────────┐
│                        ALUR SSO LENGKAP                              │
└─────────────────────────────────────────────────────────────────────┘

1. [E-Portal] User klik tombol "Buka SIAKAD"
   ↓
2. [E-Portal] Redirect ke URL callback Anda:
   https://siakad.uika-bogor.ac.id/sso/callback
   ?token=eyJhbGci...
   &appModule_id=4
   &role_id=2
   ↓
3. [SIAKAD] Terima request di /sso/callback
   ↓
4. [SIAKAD → SSO] POST /api/sso/introspect
   Header: X-SSO-Client-ID, X-SSO-Client-Secret
   Body token dari step 2
   ↓
5. [SSO → SIAKAD] Response: user data + permissions
   ↓
6. [SIAKAD] Simpan/update user di DB lokal berdasarkan sso_id
   ↓
7. [SIAKAD] Buat sesi lokal (session atau JWT sendiri)
   ↓
8. [SIAKAD] Lookup role kontekstual dari DB lokal
   (contoh: Kaprodi Prodi TI)
   ↓
9. [SIAKAD] User berhasil masuk dengan permission lengkap
```

---

## 5. Skema Database Sub-Aplikasi

### Tabel `users` (lokal sub-aplikasi)

```sql
CREATE TABLE users (
    id          BIGINT PRIMARY KEY AUTO_INCREMENT,
    
    -- WAJIB: Foreign key ke SSO
    -- Simpan public_id dari SSO, bukan email/NIDN (karena bisa berubah)
    sso_id      VARCHAR(36) UNIQUE NOT NULL,  -- UUID dari SSO (field: sso_id)
    
    -- Cache identitas dari SSO (update setiap login)
    name        VARCHAR(255) NOT NULL,
    email       VARCHAR(255),
    nidn        VARCHAR(50) NULLABLE,
    npm         VARCHAR(50) NULLABLE,
    nip         VARCHAR(50) NULLABLE,
    image       VARCHAR(500) NULLABLE,
    
    -- Status dari SSO (sync saat login)
    is_active   BOOLEAN DEFAULT TRUE,
    
    -- Audit
    last_sso_login_at TIMESTAMP NULLABLE,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Tabel `contextual_roles` (contoh untuk SIAKAD)

```sql
-- Role kontekstual user di sub-aplikasi ini
-- Ini BUKAN data SSO — ini milik sub-aplikasi sepenuhnya
CREATE TABLE user_contextual_roles (
    id            BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id       BIGINT REFERENCES users(id),
    
    -- Role kontekstual (Kaprodi, Dekan, Staff, Wali Akademik, dll.)
    role_name     VARCHAR(100) NOT NULL,
    
    -- Konteks organisasi (opsional, sesuai kebutuhan)
    context_type  VARCHAR(50) NULLABLE,  -- 'prodi', 'fakultas', 'unit'
    context_id    BIGINT NULLABLE,        -- ID prodi/fakultas/unit
    
    -- Audit
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at    TIMESTAMP NULLABLE       -- untuk role sementara
);
```

---

## 6. Contoh Middleware Laravel

### `app/Http/Middleware/SsoAuthMiddleware.php`

```php
<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SsoAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Ambil token dari Bearer header atau cookie
        $token = $request->bearerToken() 
            ?? $request->cookie('uika_sso_token');

        if (!$token) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Cache hasil introspect 5 menit untuk kurangi load ke SSO
        $cacheKey = 'sso_user_' . md5($token);
        $ssoData  = Cache::remember($cacheKey, 300, function () use ($token) {
            return $this->introspect($token);
        });

        if (!$ssoData || !($ssoData['valid'] ?? false)) {
            Cache::forget($cacheKey); // Hapus cache jika token tidak valid
            return response()->json(['message' => 'Invalid or expired SSO token'], 401);
        }

        if (!($ssoData['access']['has_access'] ?? false)) {
            return response()->json(['message' => 'Access denied to this module'], 403);
        }

        // Simpan/update user di database lokal
        $user = $this->syncUser($ssoData['user'], $ssoData['access']['permissions'] ?? []);

        // Inject ke request
        $request->attributes->set('sso_user', $user);
        $request->attributes->set('sso_permissions', $ssoData['access']['permissions'] ?? []);
        $request->attributes->set('sso_data', $ssoData);

        // Agar bisa pakai auth()->user() di Laravel
        auth()->setUser($user);

        return $next($request);
    }

    private function introspect(string $token): ?array
    {
        try {
            $response = Http::withHeaders([
                'X-SSO-Client-ID'     => config('sso.client_id'),
                'X-SSO-Client-Secret' => config('sso.client_secret'),
                'Authorization'       => 'Bearer ' . $token,
            ])->post(config('sso.base_url') . '/api/sso/introspect', [
                // Kirim sebagai query param
            ])->throw();

            // Alternatif jika pakai query param:
            // ->post(config('sso.base_url') . '/api/sso/introspect?appModule_id=' . config('sso.module_id'));

            return $response->json();
        } catch (\Exception $e) {
            Log::error('SSO introspect failed: ' . $e->getMessage());
            return null;
        }
    }

    private function syncUser(array $ssoUser, array $permissions): User
    {
        // Update atau buat user lokal berdasarkan sso_id
        return User::updateOrCreate(
            ['sso_id' => $ssoUser['sso_id']],
            [
                'name'              => $ssoUser['name'],
                'email'             => $ssoUser['email'],
                'nidn'              => $ssoUser['nidn'],
                'npm'               => $ssoUser['npm'],
                'nip'               => $ssoUser['nip'],
                'image'             => $ssoUser['image'],
                'is_active'         => $ssoUser['is_active'],
                'institutional_role'=> $ssoUser['institutional_role'],
                'last_sso_login_at' => now(),
            ]
        );
    }
}
```

### Daftarkan di `Kernel.php` sub-aplikasi

```php
'sso.auth' => \App\Http\Middleware\SsoAuthMiddleware::class,
```

### Gunakan di Routes sub-aplikasi

```php
Route::middleware('sso.auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/nilai', [NilaiController::class, 'index']);
    // ...
});
```

### Config `config/sso.php`

```php
<?php
return [
    'base_url'      => env('SSO_BASE_URL', 'http://localhost:8000'),
    'client_id'     => env('SSO_CLIENT_ID'),
    'client_secret' => env('SSO_CLIENT_SECRET'),
    'module_id'     => env('SSO_APP_MODULE_ID'),
];
```

---

## 7. Contoh Middleware Express.js (Node)

```javascript
// middleware/ssoAuth.js
const axios = require('axios');

const SSO_BASE_URL    = process.env.SSO_BASE_URL;
const SSO_CLIENT_ID   = process.env.SSO_CLIENT_ID;
const SSO_CLIENT_SECRET = process.env.SSO_CLIENT_SECRET;
const SSO_MODULE_ID   = process.env.SSO_APP_MODULE_ID;

// Simple in-memory cache (gunakan Redis di production)
const cache = new Map();

async function ssoAuth(req, res, next) {
    const token = req.headers.authorization?.replace('Bearer ', '') 
                  ?? req.cookies?.uika_sso_token;

    if (!token) {
        return res.status(401).json({ message: 'Unauthorized' });
    }

    // Check cache
    const cacheKey = `sso_${require('crypto').createHash('md5').update(token).digest('hex')}`;
    let ssoData = cache.get(cacheKey);

    if (!ssoData) {
        try {
            const response = await axios.post(
                `${SSO_BASE_URL}/api/sso/introspect?appModule_id=${SSO_MODULE_ID}`,
                {},
                {
                    headers: {
                        'Authorization'       : `Bearer ${token}`,
                        'X-SSO-Client-ID'     : SSO_CLIENT_ID,
                        'X-SSO-Client-Secret' : SSO_CLIENT_SECRET,
                    }
                }
            );
            ssoData = response.data;

            // Cache 5 menit
            cache.set(cacheKey, ssoData);
            setTimeout(() => cache.delete(cacheKey), 5 * 60 * 1000);
        } catch (err) {
            return res.status(401).json({ message: 'Invalid SSO token' });
        }
    }

    if (!ssoData?.valid) {
        cache.delete(cacheKey);
        return res.status(401).json({ message: 'Token invalid or expired' });
    }

    if (!ssoData?.access?.has_access) {
        return res.status(403).json({ message: 'Access denied to this module' });
    }

    // Inject ke request
    req.ssoUser        = ssoData.user;
    req.ssoPermissions = ssoData.access?.permissions ?? [];

    next();
}

module.exports = ssoAuth;
```

---

## 8. Contoh Response Lengkap

### `POST /api/sso/introspect` — Token Valid (Scoped / Redirect Token)

Response di bawah menunjukkan user masuk dengan scoped token (dari redirect). Detail role kontekstual, unit, dan permission langsung disediakan oleh SSO:

```json
{
  "status": 200,
  "valid": true,
  "message": "Token is valid.",
  "user": {
    "sso_id": "550e8400-e29b-41d4-a716-446655440000",
    "name": "Dr. Budi Santoso, M.T.",
    "email": "budi@uika.ac.id",
    "nidn": "0012345678",
    "nip": null,
    "npm": null,
    "phone": "081234567890",
    "image": "https://sso.uika-bogor.ac.id/storage/profiles/photos/budi.jpg",
    "is_active": true,
    "email_verified": true,
    "institutional_role": "dosen",
    "last_login_at": "2026-05-24T10:30:00+07:00",
    "registered_at": "2024-04-01T00:00:00+07:00"
  },
  "access": {
    "has_access": true,
    "module": {
      "id": 4,
      "name": "SIAKAD (Akademik)",
      "url": "http://localhost:8081/sso/callback"
    },
    "role_id": 2,
    "role_name": "kaprodi",
    "unit_id": 5,
    "unit_name": "Teknik Informatika",
    "permissions": [
      "siakad.view",
      "siakad.input_nilai"
    ]
  },
  "sso_meta": {
    "issued_by": "E-Portal UIKA",
    "token_expires_at": "2026-05-25T10:30:00+07:00",
    "introspected_at": "2026-05-24T12:00:00+07:00",
    "client_name": "SIAKAD UIKA"
  }
}
```

> **Optimasi Lokal (Tanpa API Call):** Karena token redirect adalah standard JWT yang ditandatangani oleh SSO, sub-aplikasi Anda dapat mendecode token secara mandiri (menggunakan library JWT seperti `firebase/php-jwt` atau `jsonwebtoken` di Node.js) dan langsung membaca klaim `role_id`, `role_name`, `unit_id`, `unit_name`, dan `permissions` secara lokal tanpa perlu memanggil `/api/sso/introspect` untuk setiap request.

### `POST /api/sso/introspect` — Token Expired


```json
{
  "status": 401,
  "valid": false,
  "message": "Token has expired. Please re-authenticate via SSO.",
  "user": null,
  "access": null
}
```

### `POST /api/sso/introspect` — Akun Nonaktif

```json
{
  "status": 403,
  "valid": true,
  "message": "User account is inactive.",
  "user": { "sso_id": "...", "name": "...", "is_active": false, ... },
  "access": {
    "has_access": false,
    "reason": "account_inactive"
  }
}
```

---

## 9. Best Practices RBAC Kontekstual

### Prinsip Utama

> SSO menjamin **identitas** dan **hak akses modul**. Sub-aplikasi yang atur **siapa bisa apa** di dalam modulnya.

### Mapping Institutional Role → Contextual Role

```php
// Contoh di SIAKAD: mapping awal saat user pertama kali login
// institutional_role dari SSO → role default di SIAKAD

$roleMapping = [
    'dosen'     => 'pengajar',   // di SIAKAD jadi pengajar
    'mahasiswa' => 'student',
    'admin'     => 'siakad_admin',
    'staff'     => 'staff',
    'user'      => 'read_only',
];

$defaultRole = $roleMapping[$ssoUser['institutional_role']] ?? 'read_only';

// Buat contextual role default jika belum ada
UserContextualRole::firstOrCreate([
    'user_id'   => $localUser->id,
    'role_name' => $defaultRole,
]);
```

### Tambahan Role Kontekstual oleh Admin Sub-Aplikasi

```php
// Admin SIAKAD assign Budi sebagai Kaprodi Prodi TI
UserContextualRole::create([
    'user_id'      => $localUser->id,
    'role_name'    => 'kaprodi',
    'context_type' => 'prodi',
    'context_id'   => 5, // ID Prodi Teknik Informatika di DB SIAKAD
]);
```

### Gate Check di Sub-Aplikasi

```php
// Cek permission kontekstual (bukan dari SSO, dari DB lokal sub-app)
if (!$user->hasContextualRole('kaprodi', 'prodi', $prodiId)) {
    abort(403, 'Anda bukan Kaprodi prodi ini.');
}
```

### Invalidasi Cache Saat User Dinonaktifkan

Rekomendasi: implementasikan endpoint webhook di sub-aplikasi.  
Ketika admin E-Portal menonaktifkan user, SSO dapat mengirim notifikasi ke semua sub-aplikasi terdaftar.

```
POST {callback_url}/sso/webhook
Body: {
  "event": "user.deactivated",
  "sso_id": "uuid-xxxx",
  "timestamp": "2026-05-24T12:00:00+07:00"
}
```

---

## 10. FAQ

**Q: Apakah sub-aplikasi perlu menyimpan password user?**  
A: **Tidak.** Password dikelola sepenuhnya oleh SSO. Sub-aplikasi hanya perlu `sso_id`.

**Q: Apa yang harus dilakukan jika token expired di tengah sesi?**  
A: Redirect user ke E-Portal untuk login ulang, atau implementasikan token refresh menggunakan endpoint `GET /api/refresh` SSO.

**Q: Apakah `sso_id` bisa berubah?**  
A: **Tidak.** `sso_id` adalah UUID yang stabil. Email, NIDN, dan nama bisa berubah — selalu gunakan `sso_id` sebagai primary reference.

**Q: Berapa lama token JWT berlaku?**  
A: Default 24 jam (1440 menit). Lihat field `token_expires_at` di response introspect.

**Q: Bagaimana jika SSO server down?**  
A: Implementasikan fallback: cache hasil introspect terakhir yang valid (misal 15 menit), sehingga user yang sudah login tidak langsung ter-logout jika SSO sesaat tidak available.

**Q: Bisakah sub-aplikasi punya lebih dari 1 SSO Module ID?**  
A: Tidak. Setiap sub-aplikasi memiliki 1 App Module di SSO. Jika butuh beberapa, hubungi admin untuk mendiskusikan konfigurasi.

---

*Dokumen ini dibuat oleh tim E-Portal UIKA. Untuk pertanyaan teknis, hubungi administrator.*
