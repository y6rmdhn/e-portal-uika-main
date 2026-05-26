# 📘 Dokumentasi Sistem — E-Portal UIKA

> **Nama Proyek:** E-Portal UIKA  
> **Framework:** Laravel 10 (PHP ^8.1)  
> **Tipe:** REST API Backend — Single Sign-On (SSO) & Manajemen Pengguna  
> **Institusi:** Universitas Ibn Khaldun (UIKA) Bogor  
> **Tanggal Dokumentasi:** 24 Mei 2026

---

## 📋 Daftar Isi

1. [Gambaran Umum Sistem](#1-gambaran-umum-sistem)
2. [Teknologi & Dependensi](#2-teknologi--dependensi)
3. [Struktur Direktori](#3-struktur-direktori)
4. [Database & Migrasi](#4-database--migrasi)
5. [Model Eloquent](#5-model-eloquent)
6. [Autentikasi & Keamanan](#6-autentikasi--keamanan)
7. [API Routes](#7-api-routes)
8. [Controllers](#8-controllers)
9. [Services](#9-services)
10. [Repositories](#10-repositories)
11. [Middleware](#11-middleware)
12. [Seeders](#12-seeders)
13. [Sistem Permission & Role](#13-sistem-permission--role)
14. [Sistem SSO (Single Sign-On)](#14-sistem-sso-single-sign-on)
15. [Fitur Logging](#15-fitur-logging)
16. [Panduan Setup & Instalasi](#16-panduan-setup--instalasi)

---

## 1. Gambaran Umum Sistem

**E-Portal UIKA** adalah sistem backend berbasis Laravel yang berfungsi sebagai:

- **Pusat Autentikasi (SSO)**: Pengguna cukup login sekali di E-Portal, lalu dapat diredirect ke aplikasi-aplikasi lain milik UIKA (SIAKAD, E-Library, Portal Keuangan, dsb.) tanpa login ulang.
- **Manajemen Pengguna**: Admin dapat mengelola data user, role, dan permission.
- **Sistem Log & Monitoring**: Mencatat aktivitas login (sukses/gagal) dan aktivitas pengguna secara detail.
- **Rate Limiting**: Proteksi brute-force login berbasis IP dan email menggunakan Laravel Cache.

### Alur Umum Sistem

```
[Pengguna] → Login via E-Portal API
    ↓
[AuthController] → Validasi kredensial + Rate Limit check
    ↓
[JWT Token] → Disimpan di HttpOnly Cookie (uika_sso_token)
    ↓
[SSO Redirect] → User di-redirect ke sub-aplikasi dengan token
    ↓
[Sub-Aplikasi] → Panggil /api/get_user untuk validasi token & ambil data permission
```

---

## 2. Teknologi & Dependensi

### Dependensi Utama (`require`)

| Package | Versi | Fungsi |
|---|---|---|
| `php` | ^8.1 | Bahasa pemrograman |
| `laravel/framework` | ^10.0 | Framework utama |
| `tymon/jwt-auth` | ^2.1 | Autentikasi berbasis JWT Token |
| `spatie/laravel-permission` | ^6.21 | Manajemen Role & Permission |
| `laravel/socialite` | ^5.16 | Login via Google OAuth |
| `laravel/passport` | ^12.4 | OAuth2 server (tersedia) |
| `laravel/sanctum` | ^3.2 | API token (tersedia) |
| `maatwebsite/excel` | ^3.1 | Export/Import data user ke Excel |
| `jenssegers/agent` | ^2.6 | Deteksi browser, platform, device |
| `guzzlehttp/guzzle` | ^7.4 | HTTP Client (request ke API eksternal) |
| `yajra/laravel-datatables-oracle` | ^10.11 | Server-side DataTables |
| `realrashid/sweet-alert` | ^7.1 | Alert UI |

### Dependensi Development (`require-dev`)

| Package | Fungsi |
|---|---|
| `fakerphp/faker` | Generate data dummy (factory/seeder) |
| `phpunit/phpunit ^10.0` | Unit testing |
| `laravel/sail` | Docker development environment |
| `spatie/laravel-ignition` | Error page yang informatif |

---

## 3. Struktur Direktori

```
e-portal-uika-main/
├── app/
│   ├── Console/                    # Artisan commands
│   ├── Exceptions/                 # Exception handler
│   ├── Exports/                    # Export Excel (UsersExport)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/                # Controller API (JWT-based)
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── UserController.php
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── PermissionController.php
│   │   │   │   ├── RoleController.php
│   │   │   │   ├── RoleHasPermissionController.php
│   │   │   │   ├── AppModuleController.php
│   │   │   │   ├── LoginLogController.php
│   │   │   │   ├── MyModuleController.php
│   │   │   │   ├── ProfileController.php
│   │   │   │   └── TxUserModulPermissionController.php
│   │   │   ├── SessionsController.php  # Login web (blade)
│   │   │   ├── SystemController.php
│   │   │   ├── UsersManagement.php
│   │   │   └── ...
│   │   ├── Helper/
│   │   │   └── ResponseBuilder.php     # Format response JSON standar
│   │   ├── Middleware/
│   │   │   ├── JwtMiddleware.php       # Validasi JWT Token
│   │   │   └── ...
│   │   ├── Requests/                   # Form Request Validation
│   │   └── Resources/                  # API Resource (transformasi data)
│   ├── Imports/                    # Import Excel (UsersImport)
│   ├── Mail/                       # Mailable classes (email)
│   ├── Models/
│   │   ├── User.php
│   │   ├── Role.php
│   │   ├── Permission.php
│   │   ├── AppModule.php
│   │   ├── TxUserModulPermission.php
│   │   ├── LoginLog.php
│   │   ├── UserActivityLog.php
│   │   ├── ModelHasRole.php
│   │   ├── RoleHasPermission.php
│   │   ├── LinkItems.php
│   │   └── Unit.php
│   ├── Providers/                  # Service Provider
│   ├── Repositories/               # Repository Pattern
│   │   ├── Interfaces/
│   │   ├── LoginLogRepository.php
│   │   ├── UserRepository.php
│   │   └── UserStatisticsRepository.php
│   ├── Services/                   # Business Logic
│   │   ├── ActivityLogService.php
│   │   ├── LoginLogService.php
│   │   ├── UserAdminService.php
│   │   └── UserStatisticsService.php
│   └── Traits/
│       └── ApiResponse.php         # Trait helper response API
├── database/
│   ├── factories/
│   ├── migrations/                 # Semua file migrasi database
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── RolePermissionSeeder.php
│       └── UserSeeder.php
├── routes/
│   ├── api.php                     # Semua route API
│   └── web.php                     # Route web (blade)
├── config/
├── resources/
├── storage/
└── public/
```

---

## 4. Database & Migrasi

### Daftar Tabel & Migrasi

| Tabel | File Migrasi | Keterangan |
|---|---|---|
| `app_module` | `2024_04_02_192927_create_permission_tables.php` | Daftar modul/aplikasi |
| `permissions` | `2024_04_02_192927_create_permission_tables.php` | Daftar permission (Spatie) |
| `roles` | `2024_04_02_192927_create_permission_tables.php` | Daftar role (Spatie) |
| `tx_user_module_permission` | `2024_04_02_192927_create_permission_tables.php` | Relasi user-modul-permission |
| `model_has_permissions` | `2024_04_02_192927_create_permission_tables.php` | Pivot Spatie |
| `model_has_roles` | `2024_04_02_192927_create_permission_tables.php` | Pivot Spatie |
| `role_has_permissions` | `2024_04_02_192927_create_permission_tables.php` | Pivot Spatie |
| `users` | `2024_04_02_192928_create_users_table.php` | Data pengguna |
| `sessions` | `2026_04_28_074237_create_sessions_table.php` | Laravel session |
| `user_login_logs` | `2026_04_29_234447_create_user_login_logs_table.php` | Log percobaan login |
| `user_activity_logs` | `2026_05_16_145730_create_user_activity_logs_table.php` | Log aktivitas pengguna |
| `password_resets` | `2014_10_12_100000_create_password_resets_table.php` | Token reset password |
| `personal_access_tokens` | `2019_12_14_000001_create_personal_access_tokens_table.php` | Sanctum token |
| `failed_jobs` | `2019_08_19_000000_create_failed_jobs_table.php` | Queue failed jobs |

---

### Skema Tabel Utama

#### Tabel `users`

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigInt (PK, auto-increment) | Primary key internal |
| `public_id` | uuid (unique) | ID publik yang aman diekspos |
| `name` | string | Nama lengkap |
| `email` | string (unique) | Email (login) |
| `nip` | string (nullable, unique) | No. Induk Pegawai |
| `nidn` | string (nullable, unique) | No. Induk Dosen Nasional |
| `npm` | string (nullable, unique) | No. Pokok Mahasiswa |
| `role_id` | foreignId → `roles` | Role default |
| `is_active` | boolean (default: false) | Status aktif akun |
| `password` | string | Password (hashed) |
| `phone` | bigInteger (nullable) | No. telepon |
| `location` | string (nullable) | Lokasi/alamat |
| `about_me` | string (nullable) | Deskripsi singkat |
| `image` | text (nullable) | Path foto profil |
| `last_login_at` | timestamp (nullable) | Waktu login terakhir |
| `email_verified_at` | timestamp (nullable) | Waktu verifikasi email |
| `deleted_at` | timestamp (nullable) | Soft delete |

#### Tabel `app_module`

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigIncrements (PK) | |
| `name` | string (unique) | Nama modul/aplikasi |
| `url` | string (nullable) | URL SSO callback modul |
| `deleted_at` | timestamp (nullable) | Soft delete |

#### Tabel `permissions`

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigIncrements (PK) | |
| `name` | string | Nama permission (misal: `siakad.view`) |
| `appModule_id` | integer (nullable) | FK ke `app_module` |
| `guard_name` | string | Guard (default: `web`) |
| `deleted_at` | timestamp (nullable) | Soft delete |

#### Tabel `tx_user_module_permission`

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigIncrements (PK) | |
| `user_id` | integer | FK ke `users` |
| `appModule_id` | integer | FK ke `app_module` |
| `role_id` | integer | FK ke `roles` |
| `permission_id` | integer | FK ke `permissions` |
| `deleted_at` | timestamp (nullable) | Soft delete |

#### Tabel `user_login_logs`

| Kolom | Tipe | Keterangan |
|---|---|---|
| `user_id` | string (nullable) | User yang login |
| `ip_address` | string | Alamat IP |
| `user_agent` | text | User-agent browser |
| `browser` | string | Nama browser |
| `browser_version` | string | Versi browser |
| `platform` | string | OS (Windows, Android, dsb.) |
| `device_type` | string | `desktop`, `mobile`, `tablet` |
| `status` | string | `success` / `failed` |
| `failure_reason` | string (nullable) | Alasan gagal login |
| `created_at` | timestamp | Waktu log |

#### Tabel `user_activity_logs`

| Kolom | Tipe | Keterangan |
|---|---|---|
| `user_id` | integer | Target user |
| `actor_id` | integer | User yang melakukan aksi |
| `type` | string | Jenis aktivitas (`login`, `logout`, dsb.) |
| `description` | string | Deskripsi aktivitas |
| `metadata` | json (nullable) | Data tambahan (IP, browser, dsb.) |
| `created_at` | timestamp | Waktu log |

---

## 5. Model Eloquent

### `User`
**File:** `app/Models/User.php`

- Implements `JWTSubject` → mendukung autentikasi JWT
- Implements `MustVerifyEmail` → email harus diverifikasi sebelum login
- Traits: `HasFactory`, `Notifiable`, `HasRoles` (Spatie), `HasUuids`
- Menggunakan `public_id` (UUID) sebagai route key aman untuk API publik
- JWT custom claims: `id` (public_id), `email`, `role`

```
Relasi:
- belongsTo(Role) via role_id
```

### `Permission`
**File:** `app/Models/Permission.php`

- Extends `Spatie\Permission\Models\Permission`
- Menambahkan field `appModule_id` untuk mengelompokkan permission per modul

```
Relasi:
- belongsTo(AppModule) via appModule_id
```

### `AppModule`
**File:** `app/Models/AppModule.php`

- Tabel: `app_module`
- Memiliki Global Scope untuk menyaring `deleted_at` (soft delete manual)

```
Relasi:
- hasMany(Permission) via appModule_id
```

### `TxUserModulPermission`
**File:** `app/Models/TxUserModulPermission.php`

- Tabel: `tx_user_module_permission`
- Menyimpan relasi spesifik antara user, modul aplikasi, role, dan permission

```
Relasi:
- belongsTo(AppModule) via appModule_id
- belongsTo(Unit) via unit_id
- belongsTo(Role) via role_id
- hasMany(RoleHasPermission) via role_id
```

### `LoginLog`
**File:** `app/Models/LoginLog.php`

- Tabel: `user_login_logs`
- Tidak menggunakan timestamps otomatis (`$timestamps = false`), `created_at` diisi manual saat boot

### `UserActivityLog`
**File:** `app/Models/UserActivityLog.php`

- Tabel: `user_activity_logs`
- Field `metadata` di-cast ke `array` (JSON)

```
Relasi:
- belongsTo(User) via user_id
- belongsTo(User) via actor_id (admin yang melakukan aksi)
```

---

## 6. Autentikasi & Keamanan

### Mekanisme Autentikasi: JWT

Sistem menggunakan **`tymon/jwt-auth`** untuk autentikasi stateless berbasis token.

**Alur Login:**
1. Pengguna POST ke `/api/auth/login` dengan `email` + `password`
2. Sistem cek rate limit (IP & email) di cache
3. Cek email sudah diverifikasi
4. JWT token dibuat via `JWTAuth::attempt()`
5. Token disimpan dalam **HttpOnly Cookie** `uika_sso_token` (SameSite: Lax/None)
6. Token juga dikembalikan dalam response body (`uika_sso_token`)

**Cookie Settings:**
| Environment | Secure | SameSite | Domain |
|---|---|---|---|
| Production | `true` | `None` | `.uika-bogor.ac.id` |
| Development | `false` | `Lax` | `null` |

### Rate Limiting (Brute-Force Protection)

**File:** `app/Services/LoginLogService.php`

| Parameter | Nilai |
|---|---|
| Maks gagal per IP (15 menit) | **10 kali** |
| Maks gagal per Email (15 menit) | **5 kali** |
| Durasi lockout | **15 menit** |
| Threshold IP mencurigakan | 10 kali gagal dalam 60 menit |

**Implementasi:**
- Menggunakan Laravel Cache sebagai penyimpanan counter
- Cache key: `login_fail_ip:{ip}`, `login_lock_ip:{ip}`, `login_fail_email:{md5(email)}`, `login_lock_email:{md5(email)}`
- Setelah login sukses, semua counter direset

### Login via Google OAuth

- Route: `GET /api/auth/google/redirect` → redirect ke halaman persetujuan Google
- Route: `GET /api/auth/google/callback` → proses callback, generate JWT, simpan cookie

**Alur Google Login:**
1. User belum terdaftar → redirect ke `/register?social_data={base64_encoded_data}`
2. User sudah terdaftar → generate JWT + simpan cookie → redirect ke `/auth/google/success`

### Verifikasi Email

Route: `GET /api/email/verify/{id}/{hash}` (middleware: `signed`)

- Menggunakan Laravel signed URL untuk memverifikasi email
- Setelah verifikasi berhasil → redirect ke `http://localhost:5173/login?verified=true`

---

## 7. API Routes

**Base URL:** `/api`

### Routes Publik (Tanpa Token)

| Method | Endpoint | Controller | Fungsi |
|---|---|---|---|
| POST | `/auth/login` | `AuthController@auth` | Login dengan email & password |
| POST | `/auth/login/tias` | `AuthController@authTias` | Login via TIAS (API eksternal) |
| POST | `/register` | `AuthController@register` | Registrasi user baru |
| POST | `/password/email` | `AuthController@sendResetLinkEmail` | Kirim link reset password |
| POST | `/password/reset` | `AuthController@resetPassword` | Reset password dengan token |
| GET | `/auth/google/redirect` | `AuthController@redirectToGoogle` | Redirect ke Google OAuth |
| GET | `/auth/google/callback` | `AuthController@handleGoogleCallback` | Callback setelah Google login |
| GET | `/email/verify/{id}/{hash}` | Closure | Verifikasi email |

### Routes Terproteksi (Wajib JWT — middleware: `jwt.verify`)

| Method | Endpoint | Controller | Fungsi |
|---|---|---|---|
| POST | `/logout` | `AuthController@logout` | Logout & invalidate token |
| GET | `/get_user` | `AuthController@get_user` | Ambil data user + permissions |
| GET | `/refresh` | `AuthController@refresh` | Refresh JWT token |
| GET | `/app_modul` | `AppModuleController@index` | Daftar modul aplikasi |
| GET | `/tx_user_modul_permission` | `TxUserModulPermissionController@index` | Daftar permission user per modul |
| GET | `/call_user` | `AuthController@call_user` | Data user + permission untuk sub-app |
| GET | `/my-modules` | `MyModuleController@index` | Modul yang dapat diakses user |
| GET | `/sso/redirect` | `AuthController@redirect` | SSO redirect ke sub-aplikasi |

#### Profile (JWT required)

| Method | Endpoint | Controller | Fungsi |
|---|---|---|---|
| GET | `/profile` | `ProfileController@show` | Lihat profil sendiri |
| POST | `/profile/update` | `ProfileController@update` | Update profil |
| POST | `/profile/change-password` | `ProfileController@changePassword` | Ganti password |

#### Admin Routes (JWT + role:admin)

**Prefix:** `/admins`

**User Management:**

| Method | Endpoint | Fungsi |
|---|---|---|
| GET | `/admins` | Daftar semua user (paginasi, filter) |
| POST | `/admins` | Buat user baru |
| GET | `/admins/{id}` | Detail satu user |
| POST/PUT | `/admins/{id}` | Update user |
| DELETE | `/admins/{id}` | Hapus user |
| PATCH | `/admins/{id}/toggle-active` | Aktifkan/nonaktifkan user |
| POST | `/admins/{id}/reset-password` | Reset password user |
| GET | `/admins/{id}/activity-logs` | Log aktivitas user |
| GET | `/admins/export` | Export user ke Excel |
| POST | `/admins/import` | Import user dari Excel |

**Dashboard:**

| Method | Endpoint | Fungsi |
|---|---|---|
| GET | `/admins/dashboard/stats` | Statistik ringkas (total user, dll.) |
| GET | `/admins/dashboard/active-users` | Chart user aktif (weekly/monthly) |
| GET | `/admins/dashboard/user-growth` | Chart pertumbuhan user |
| GET | `/admins/dashboard/recent-activity` | Aktivitas login terbaru |
| GET | `/admins/dashboard/idle-users` | User yang tidak aktif |
| GET | `/admins/dashboard/role-distribution` | Distribusi user per role |
| GET | `/admins/dashboard/login-heatmap` | Heatmap login per jam |
| POST | `/admins/dashboard/clear-cache` | Hapus cache (super-admin only) |

**Security Logs:**

| Method | Endpoint | Fungsi |
|---|---|---|
| GET | `/admins/security/logs` | Semua log login |
| GET | `/admins/security/logs/user/{id}` | Log login milik user tertentu |
| GET | `/admins/security/suspicious-ips` | IP mencurigakan |
| GET | `/admins/security/rate-limit-status` | Status rate limit saat ini |
| DELETE | `/admins/security/logs/purge` | Hapus log lama |

**App Modules (CRUD):**

| Method | Endpoint | Fungsi |
|---|---|---|
| GET | `/admins/app-modules` | Daftar semua modul |
| POST | `/admins/app-modules` | Buat modul baru |
| GET | `/admins/app-modules/{id}` | Detail modul |
| PUT | `/admins/app-modules/{id}` | Update modul |
| DELETE | `/admins/app-modules/{id}` | Hapus modul |

**Roles (CRUD):**

| Method | Endpoint | Fungsi |
|---|---|---|
| GET | `/admins/roles` | Daftar semua role |
| POST | `/admins/roles` | Buat role baru |
| POST | `/admins/roles/assign` | Assign role ke user |
| POST | `/admins/roles/unassign` | Unassign role dari user |
| GET | `/admins/roles/{id}` | Detail role |
| PUT | `/admins/roles/{id}` | Update role |
| DELETE | `/admins/roles/{id}` | Hapus role |

**Permissions (CRUD + Bulk):**

| Method | Endpoint | Fungsi |
|---|---|---|
| GET | `/admins/permissions` | Daftar semua permission |
| POST | `/admins/permissions` | Buat permission |
| GET | `/admins/permissions/{id}` | Detail permission |
| PUT | `/admins/permissions/{id}` | Update permission |
| DELETE | `/admins/permissions/{id}` | Hapus permission |
| POST | `/admins/permissions/bulk` | Bulk create permissions |
| PUT | `/admins/permissions/bulk` | Bulk update permissions |
| DELETE | `/admins/permissions/bulk` | Bulk delete permissions |

**Role ↔ Permission Assignment:**

| Method | Endpoint | Fungsi |
|---|---|---|
| GET | `/admins/role-permissions` | Daftar relasi role-permission |
| POST | `/admins/role-permissions/assign` | Assign permission ke role |
| POST | `/admins/role-permissions/unassign` | Unassign permission dari role |
| POST | `/admins/role-permissions/sync` | Sync semua permission untuk role |

---

## 8. Controllers

### `AuthController` (`app/Http/Controllers/Api/AuthController.php`)

Controller utama untuk semua urusan autentikasi.

| Method | Fungsi |
|---|---|
| `register()` | Registrasi user baru + assign role + kirim email verifikasi |
| `auth()` | Login dengan email/password, cek rate limit, generate JWT + cookie |
| `authTias()` | Login via API TIAS eksternal (validasi ke `api-tias.ti.ft.uika-bogor.ac.id`) |
| `logout()` | Invalidate JWT token + hapus cookie |
| `refresh()` | Refresh JWT token yang hampir expired |
| `get_user()` | Ambil data user lengkap + daftar permissions per modul |
| `call_user()` | Ambil data user + permission detail untuk sub-aplikasi |
| `sendResetLinkEmail()` | Kirim email link reset password |
| `resetPassword()` | Proses reset password dengan token |
| `redirectToGoogle()` | Inisiasi alur Google OAuth |
| `handleGoogleCallback()` | Handle callback dari Google OAuth |
| `redirect()` | SSO redirect ke sub-aplikasi dengan token di query string |

### `UserController` (`app/Http/Controllers/Api/UserController.php`)

Manajemen user oleh admin.

| Method | Fungsi |
|---|---|
| `index()` | Daftar user dengan filter (role, is_active, search) + paginasi |
| `store()` | Buat user baru (via `StoreAdminRequest` + `UserAdminService`) |
| `show()` | Detail user berdasarkan ID |
| `update()` | Update data user |
| `destroy()` | Hapus user |
| `toggleActive()` | Toggle status aktif/nonaktif user |
| `resetPassword()` | Reset password user oleh admin |
| `export()` | Export daftar user ke file Excel |
| `import()` | Import user dari file Excel (maks 5MB) |
| `activityLogs()` | Ambil log aktivitas milik user |

### `PermissionController` (`app/Http/Controllers/Api/PermissionController.php`)

CRUD permission dengan fitur bulk operations.

| Method | Fungsi |
|---|---|
| `index()` | Daftar permission, dapat difilter by `appModule_id` |
| `show()` | Detail permission (beserta role yang memilikinya) |
| `store()` | Buat permission baru (wajib `appModule_id`) |
| `update()` | Update permission |
| `destroy()` | Hapus permission |
| `bulkStore()` | Bulk create, otomatis skip duplikat |
| `bulkUpdate()` | Bulk update, cek uniqueness |
| `bulkDestroy()` | Bulk delete berdasarkan array ID |

### `DashboardController` (`app/Http/Controllers/Api/DashboardController.php`)

Endpoint data untuk dashboard admin.

| Method | Data yang Dikembalikan |
|---|---|
| `stats()` | Total user, user aktif, tidak aktif, dll. |
| `activeUsersChart()` | Data bar chart login harian/mingguan/bulanan |
| `userGrowth()` | Data line chart pertumbuhan user |
| `recentActivity()` | N log login terbaru |
| `idleUsers()` | User yang tidak login selama X hari |
| `roleDistribution()` | Jumlah user per role (donut chart) |
| `loginHeatmap()` | Heatmap login per jam dalam seminggu |
| `clearCache()` | Flush semua cache dashboard (super-admin only) |

---

## 9. Services

### `LoginLogService` (`app/Services/LoginLogService.php`)

Business logic untuk pencatatan dan proteksi login.

**Konstanta:**
```php
MAX_ATTEMPTS_PER_IP    = 10   // per IP dalam 15 menit
MAX_ATTEMPTS_PER_EMAIL = 5    // per email dalam 15 menit
LOCKOUT_MINUTES        = 15
SUSPICIOUS_THRESHOLD   = 10   // trigger IP mencurigakan
```

**Metode Publik:**
- `logSuccess(userId, request)` — catat login sukses + reset counter
- `logFailure(request, reason)` — catat login gagal + increment counter
- `getAllLogs(filters)` — ambil semua log (paginasi)
- `getLogsByUser(userId, filters)` — log milik user tertentu
- `getSuspiciousIps()` — IP dengan banyak kegagalan (cached 5 menit)
- `isIpBlocked(ip)` — cek apakah IP sedang di-lockout
- `isEmailBlocked(email)` — cek apakah email sedang di-lockout
- `getLockoutRemainingSeconds(identifier, type)` — sisa detik lockout
- `getRateLimitStatus(ip, email)` — ringkasan status rate limit
- `purgeOldLogs(days)` — hapus log lebih dari X hari

### `ActivityLogService` (`app/Services/ActivityLogService.php`)

Pencatatan aktivitas pengguna (audit trail).

**Tipe Aktivitas:**
```php
TYPE_LOGIN           = 'login'
TYPE_LOGOUT          = 'logout'
TYPE_UPDATE_PROFILE  = 'update_profile'
TYPE_CHANGE_PASSWORD = 'change_password'
TYPE_RESET_PASSWORD  = 'reset_password'
TYPE_APP_ACCESS      = 'app_access'
```

**Metode:**
- `log(type, description, userId, actorId, metadata)` — catat aktivitas
- `logForCurrentUser(type, description, metadata)` — catat untuk user JWT saat ini (silent fail)
- `getByUser(userId, filters)` — ambil log aktivitas user (filter by type, date range)
- `purgeOldLogs(days=180)` — hapus log lebih dari 6 bulan

### `UserAdminService` (`app/Services/UserAdminService.php`)

Business logic manajemen user oleh admin (CRUD, toggle aktif, reset password).

### `UserStatisticsService` (`app/Services/UserStatisticsService.php`)

Kalkulasi statistik untuk dashboard (stats cards, chart data, heatmap login).

---

## 10. Repositories

Menggunakan **Repository Pattern** untuk abstraksi query database.

### `LoginLogRepository` + Interface
- `create(data)` — simpan log
- `getAllLogs(filters)` — query dengan filter status, IP, tanggal
- `getLogsByUser(userId, filters)` — log per user
- `getSuspiciousIps(threshold, minutes)` — IP dengan banyak gagal
- `deleteOldLogs(days)` — hapus log lama

### `UserRepository`
- Query-query terkait manajemen user

### `UserStatisticsRepository`
- Query agregasi untuk statistik dashboard

---

## 11. Middleware

### `JwtMiddleware` (`app/Http/Middleware/JwtMiddleware.php`)

Middleware utama untuk proteksi route API.

**Alur:**
1. Cek apakah request menggunakan Bearer token
2. Jika tidak ada Bearer token tapi ada cookie `uika_sso_token`, ambil token dari cookie dan set sebagai `Authorization` header
3. Parse dan autentikasi token via `FacadesJWTAuth::parseToken()->authenticate()`
4. Set authenticated user ke `auth()->setUser($user)`

**Response Error:**
- Token tidak valid → HTTP 401 "Token is Invalid"
- Token expired → HTTP 401 "Token is Expired"
- Token tidak ditemukan → HTTP 401 "Authorization Token not found"

**Alias Middleware:** `jwt.verify` (didaftarkan di `Kernel.php`)

---

## 12. Seeders

### `DatabaseSeeder` → memanggil:
1. `RolePermissionSeeder::class`
2. `UserSeeder::class`

### `RolePermissionSeeder` (`database/seeders/RolePermissionSeeder.php`)

Membuat data awal untuk:

**App Modules yang dibuat:**

| Key | Nama | URL |
|---|---|---|
| `users` | Manajemen User | `/admin/user-management` |
| `roles` | Roles | `/admin/roles` |
| `permissions` | Permissions | `/admin/permissions` |
| `siakad` | SIAKAD (Akademik) | `http://localhost:8081/sso/callback` |
| `elibrary` | E-Library UIKA | `http://localhost:8082/sso/callback` |
| `finance` | Portal Keuangan | `http://localhost:8083/sso/callback` |

**Permissions per modul:**

| Modul | Permissions |
|---|---|
| `users` | `view`, `create`, `edit`, `delete` |
| `roles` | `view`, `create`, `edit`, `delete` |
| `permissions` | `view`, `create`, `edit`, `delete` |
| `siakad` | `view`, `input_nilai` |
| `elibrary` | `view`, `pinjam` |
| `finance` | `view`, `bayar` |

**Permissions per role:**

| Role | Permissions |
|---|---|
| `admin` | Semua permission |
| `dosen` | `siakad.view`, `siakad.input_nilai`, `elibrary.view` |
| `mahasiswa` | `siakad.view`, `elibrary.view`, `elibrary.pinjam`, `finance.view` |
| `user` | `siakad.view`, `elibrary.view` |

### `UserSeeder` (`database/seeders/UserSeeder.php`)

Membuat 4 user default:

| Email | Nama | Role | Password |
|---|---|---|---|
| `admin@gmail.com` | Administrator | `admin` | `password` |
| `dosen@gmail.com` | Dr. Ahmad Dosen, M.T. | `dosen` | `password` |
| `mahasiswa@gmail.com` | Budi Mahasiswa | `mahasiswa` | `password` |
| `user@gmail.com` | User Biasa | `user` | `password` |

---

## 13. Sistem Permission & Role

Sistem menggunakan **Spatie Laravel Permission** dengan customisasi:

### Hierarki Role

```
super-admin
    └── admin
            ├── dosen
            ├── mahasiswa
            └── user
```

### Permission Naming Convention

Format: `{module}.{action}`

Contoh:
- `siakad.view`
- `siakad.input_nilai`
- `elibrary.pinjam`
- `finance.bayar`
- `users.create`

### Cara Pengambilan Permission di `/api/get_user`

```php
// Admin & Super-Admin → ambil SEMUA permission di sistem
if ($user->hasAnyRole(['admin', 'super-admin'])) {
    $allPermissions = Permission::all();
} else {
    // User biasa → ambil permission yang memang dimilikinya
    $allPermissions = $user->getAllPermissions();
}

// Output:
// permissions          → flat array nama permission
// permissions_by_module → group by appModule_id
// module_permissions   → permission untuk appModule_id tertentu (via query param)
```

---

## 14. Sistem SSO (Single Sign-On)

### Alur SSO

```
1. User login ke E-Portal → dapat JWT token + cookie

2. Sub-aplikasi (SIAKAD, dll.) tampilkan tombol "Login via E-Portal"
   → Sub-app request ke: GET /api/sso/redirect?target_url=...&role_id=...&appModule_id=...

3. E-Portal validasi cookie/token → redirect ke sub-app dengan token di URL:
   {target_url}?token=...&role_id=...&appModule_id=...&unit_id=...

4. Sub-app terima token → panggil GET /api/get_user?appModule_id=...
   → Dapatkan data user + permission khusus untuk modul itu

5. Sub-app validasi permission sebelum izinkan akses fitur tertentu
```

### Endpoint SSO

**`GET /api/sso/redirect`** (JWT required)

Query params:
- `target_url` (wajib) — URL callback sub-aplikasi
- `role_id` (wajib) — ID role yang digunakan
- `appModule_id` (wajib) — ID modul yang dituju
- `unit_id` (opsional, default: `1`) — ID unit/departemen

Response:
```json
{
  "status": 200,
  "redirect_url": "http://localhost:8081/sso/callback?token=...&role_id=1&appModule_id=4&unit_id=1"
}
```

**`GET /api/call_user`** (JWT required)

Digunakan sub-aplikasi untuk mengambil detail user + permission dalam konteks modul tertentu.

Query params: `role_id`, `appModule_id`

### Pendekatan Cookie

Token disimpan di cookie HttpOnly `uika_sso_token` sehingga:
- Tidak dapat diakses via JavaScript (aman dari XSS)
- Dikirim otomatis oleh browser ke domain yang sama (atau subdomain dengan `Domain=.uika-bogor.ac.id`)

---

## 15. Fitur Logging

### Login Log

Setiap percobaan login (sukses maupun gagal) dicatat dengan:
- IP address
- Browser & versi
- Platform/OS
- Tipe device
- Status: `success` / `failed`
- Alasan gagal: `invalid_credentials`, `email_not_verified`, dsb.

### Activity Log

Mencatat aktivitas penting pengguna:

| Tipe | Trigger |
|---|---|
| `login` | Saat login berhasil |
| `logout` | Saat logout |
| `update_profile` | Saat update profil |
| `change_password` | Saat ganti password |
| `reset_password` | Saat reset password |
| `app_access` | Saat akses sub-aplikasi via SSO |

### Pembersihan Log Otomatis

- Login logs → `purgeOldLogs(90)` — hapus log > 90 hari
- Activity logs → `purgeOldLogs(180)` — hapus log > 6 bulan

---

## 16. Panduan Setup & Instalasi

### Prasyarat

- PHP >= 8.1
- Composer
- MySQL / MariaDB
- Node.js & NPM (untuk asset frontend jika ada)

### Langkah Instalasi

```bash
# 1. Clone atau masuk ke direktori proyek
cd e-portal-uika-main

# 2. Install dependensi PHP
composer install

# 3. Salin file environment
cp .env.example .env

# 4. Generate application key
php artisan key:generate

# 5. Konfigurasi .env (database, mail, JWT, Google OAuth)
# Edit file .env sesuai environment

# 6. Generate JWT secret
php artisan jwt:secret

# 7. Jalankan migrasi database
php artisan migrate

# 8. Jalankan seeder
php artisan db:seed

# 9. Buat symlink storage (untuk foto profil)
php artisan storage:link

# 10. Jalankan server
php artisan serve
```

### Konfigurasi `.env` Penting

```env
APP_URL=http://localhost:8000
DB_HOST=127.0.0.1
DB_DATABASE=your_db_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

JWT_SECRET=your_jwt_secret   # generate via: php artisan jwt:secret

MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_user
MAIL_PASSWORD=your_mailtrap_pass
MAIL_FROM_ADDRESS=noreply@uika.ac.id

GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=http://localhost:8000/api/auth/google/callback
```

### Menjalankan Seeder Ulang

```bash
# Seeder lengkap (roles, permissions, user default)
php artisan db:seed

# Seeder spesifik
php artisan db:seed --class=RolePermissionSeeder
php artisan db:seed --class=UserSeeder
```

---

## 📎 Catatan Penting

> [!NOTE]
> Sistem ini adalah **backend-only API**. Frontend (Vue.js/React di `localhost:5173`) berjalan terpisah.

> [!IMPORTANT]
> Endpoint `/api/admins/*` membutuhkan **role `admin`** (via Spatie middleware `role:admin`).  
> Endpoint `/api/admins/dashboard/clear-cache` membutuhkan **role `super-admin`**.

> [!WARNING]
> Fungsi `authTias()` menggunakan hardcoded credential (`su-admin@gmail.com`, `qwe123QWE!@#`). **Jangan gunakan di production tanpa mengubah implementasi ini.**

> [!TIP]
> Gunakan `GET /api/get_user?appModule_id={id}` dari sub-aplikasi untuk mendapatkan permission spesifik user untuk modul tersebut tanpa perlu query tambahan.

---

*Dokumentasi ini dibuat secara otomatis berdasarkan analisis kode sumber E-Portal UIKA. Terakhir diperbarui: 24 Mei 2026.*
