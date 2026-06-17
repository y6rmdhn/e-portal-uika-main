# 🏛️ CONTEXT KNOWLEDGE: IMPLEMENTASI RBAC E-PORTAL UNIVERSITAS IBN KHALDUN BOGOR

Dokumen ini berisi spesifikasi teknis lengkap, struktur kode, skema database, alur proses, dan hasil pengujian **modul Role-Based Access Control (RBAC)** pada aplikasi **E-Portal UIKA**. 

Dokumen ini dirancang sebagai berkas pengetahuan (*knowledge file*) untuk **AI Agent** agar dapat menghasilkan laporan skripsi yang presisi dan sinkron dengan aplikasi nyata, mengikuti struktur model **Waterfall** (Requirement, Design, Implementation, Verification, Deployment).

---

## 👤 IDENTITAS PENELITIAN
* **Penulis / Mahasiswa:** Maulana Ikhsan
* **NPM:** 221106043035
* **Program Studi:** Teknik Informatika
* **Fakultas:** Teknik dan Sains
* **Universitas:** Universitas Ibn Khaldun Bogor
* **Judul Penelitian:** Rancang Bangun Sistem Otorisasi Terpusat Menggunakan Role-Based Access Control (RBAC) pada E-Portal Universitas Ibn Khaldun Bogor

---

## 🏗️ 1. ARCHITECTURE & TECHNOLOGY STACK (SCOPE: RBAC)

Modul RBAC pada sistem E-Portal UIKA menggunakan arsitektur **RESTful Client-Server** dengan pemisahan penuh antara Frontend dan Backend:

1. **Backend API:**
   * **Framework:** Laravel 10.x (PHP 8.x)
   * **Library Autentikasi:** `tymon/jwt-auth` (JSON Web Token) untuk otentikasi stateless.
   * **Library Otorisasi:** `spatie/laravel-permission` untuk pengelolaan data role dan permission di tingkat database.
   * **Database:** MySQL 8.x.

2. **Frontend UI:**
   * **Framework:** React JS (Vite + TypeScript/JavaScript ES6+)
   * **HTTP Client:** Axios untuk request data dari REST API.
   * **State Management:** React Context API (`AuthContext`) untuk global auth state.
   * **Routing:** React Router DOM.

---

## 🗄️ 2. DATABASE SCHEMA (ERD)

Skema basis data dirancang untuk mendukung otorisasi standar (global role/permission) yang terintegrasi dengan modul aplikasi (**AppModule**) secara 1-ke-1 dengan kredensial client SSO (**SsoClient**).

### A. Tabel-Tabel Inti (MySQL)
1. **`users`**
   * `id` (bigint, PK, auto_increment)
   * `public_id` (char(36), unique) -> UUID untuk keamanan API.
   * `name` (varchar(255))
   * `email` (varchar(255), unique)
   * `password` (varchar(255))
   * `role_id` (bigint, FK to `roles`) -> Peran global pengguna.
   * `is_active` (boolean, default true)
   * `nidn` / `npm` / `nip` (varchar(50), nullable)
   * `last_login_at` (timestamp, nullable)

2. **`roles`** (Tabel Spatie)
   * `id` (bigint, PK, auto_increment)
   * `name` (varchar(255)) -> Nama role (contoh: `super-admin`, `admin`, `dosen`, `mahasiswa`, `staff`, `user`).
   * `guard_name` (varchar(255), default 'web')

3. **`permissions`** (Tabel Spatie Kustom)
   * `id` (bigint, PK, auto_increment)
   * `name` (varchar(255)) -> Nama permission (contoh: `siakad.view`, `siakad.input_nilai`, `elibrary.pinjam`).
   * `guard_name` (varchar(255), default 'web')
   * `appModule_id` (bigint, FK to `app_module`) -> Menghubungkan permission dengan modul aplikasi spesifik.

4. **`app_module`** (Tabel Kustom Modul)
   * `id` (bigint, PK, auto_increment)
   * `name` (varchar(255)) -> Nama modul aplikasi (contoh: `SIAKAD UIKA`, `E-Library UIKA`, `Portal Keuangan UIKA`).
   * `url` (varchar(255)) -> URL redirect modul.

5. **`sso_clients`** (Tabel Kustom Kredensial SSO - Relasi 1-to-1 ke `app_module`)
   * `id` (bigint, PK, auto_increment)
   * `app_module_id` (bigint, FK to `app_module`, cascade delete) -> Menghubungkan kredensial ke satu modul aplikasi secara 1-ke-1.
   * `name` (varchar(255)) -> Nama client (misal: "SIAKAD Client").
   * `client_id` (char(36)/uuid, unique) -> ID publik pengenal client.
   * `client_secret` (varchar(255)) -> Hash kunci rahasia client (tidak disimpan plaintext).
   * `callback_url` (varchar(255)) -> URL redirect pencocokan whitelist.
   * `is_active` (boolean, default true)
   * `total_requests` (bigint) -> Statistik hit API introspect.
   * `last_used_at` (timestamp, nullable)

6. **`role_has_permissions`** (Tabel Pivot Spatie)
   * `role_id` (bigint, FK to `roles`, PK)
   * `permission_id` (bigint, FK to `permissions`, PK)

7. **`model_has_roles`** (Tabel Pivot Spatie)
   * `role_id` (bigint, FK to `roles`, PK)
   * `model_type` (varchar(255), PK) -> `App\Models\User`
   * `model_id` (bigint, PK) -> ID user.

8. **`tx_user_module_permission`** (Tabel Transaksi Hak Akses)
   * `id` (bigint, PK, auto_increment)
   * `user_id` (bigint, FK to `users`)
   * `appModule_id` (bigint, FK to `app_module`)
   * `role_id` (bigint, FK to `roles`)
   * `permission_id` (bigint, FK to `permissions`)

---

## 🛣️ 3. BACKEND ROUTES & MIDDLEWARE (LARAVEL)

### A. API Endpoints (`routes/api.php`)
Seluruh endpoint manajemen modul dan kredensial disatukan dan dilindungi oleh middleware **`jwt.verify`** dan **`role:admin`**:

```php
// Rute Autentikasi Umum
Route::post('/auth/login', [AuthController::class, 'auth']);
Route::group(['middleware' => ['jwt.verify']], function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/get_user', [AuthController::class, 'get_user']);
    Route::get('/refresh', [AuthController::class, 'refresh']);
    Route::get('/app_modul', [AppModuleController::class, 'index']);
    Route::get('/my-modules', [MyModuleController::class, 'index']);

    // Rute Khusus Admin (Otorisasi RBAC & Unified AppModule)
    Route::middleware(['role:admin'])->prefix('admins')->group(function () {
        // CRUD User
        Route::apiResource('/', UserController::class);
        Route::patch('/{id}/toggle-active', [UserController::class, 'toggleActive']);
        
        // CRUD AppModule & Kredensial SSO Terpadu (1-to-1)
        Route::apiResource('/app-modules', AppModuleController::class);
        Route::post('/app-modules/{id}/reset-secret', [AppModuleController::class, 'resetSecret']);
        
        // CRUD Roles
        Route::apiResource('/roles', RoleController::class);
        Route::post('/roles/assign', [RoleController::class, 'assignRole']);
        Route::post('/roles/unassign', [RoleController::class, 'unassignRole']);
        
        // CRUD Permissions (Bulk)
        Route::apiResource('/permissions', PermissionController::class);
        Route::post('/permissions/bulk', [PermissionController::class, 'bulkStore']);
        Route::put('/permissions/bulk', [PermissionController::class, 'bulkUpdate']);
        Route::delete('/permissions/bulk', [PermissionController::class, 'bulkDestroy']);
        
        // Pemetaan Role-Permission (Sync)
        Route::prefix('role-permissions')->group(function () {
            Route::get('/', [RoleHasPermissionController::class, 'index']);
            Route::post('/assign', [RoleHasPermissionController::class, 'assign']);
            Route::post('/unassign', [RoleHasPermissionController::class, 'unassign']);
            Route::post('/sync', [RoleHasPermissionController::class, 'sync']);
        });
    });
});
```

---

## 💻 4. STRUCTURE & IMPLEMENTATION CODEBASE (UNIFIED DESIGN)

### A. Sisi Backend Laravel
1. **[app/Models/AppModule.php](file:///c:/website/laravel/e-portal-uika-main/app/Models/AppModule.php):**
   Mendefinisikan relasi `hasOne` ke `SsoClient`:
   ```php
   public function ssoClient() {
       return $this->hasOne(SsoClient::class, 'app_module_id', 'id');
   }
   ```
2. **[app/Models/SsoClient.php](file:///c:/website/laravel/e-portal-uika-main/app/Models/SsoClient.php):**
   Mendefinisikan relasi `belongsTo` ke `AppModule` dan menyertakan `app_module_id` pada `$fillable`.
3. **[app/Http/Controllers/Api/AppModuleController.php](file:///c:/website/laravel/e-portal-uika-main/app/Http/Controllers/Api/AppModuleController.php):**
   Mengelola siklus hidup terpadu:
   * **`store`:** Saat AppModule dibuat, backend otomatis men-generate UUID `client_id` dan random hash `client_secret` (SsoClient), lalu mengembalikan `plain_secret` sekali saja.
   * **`update`:** Mengupdate nama dan URL callback SSO pada client agar tetap sinkron.
   * **`destroy`:** Soft-delete AppModule yang secara otomatis memicu *cascade delete* pada kredensial SsoClient di database.
   * **`resetSecret`:** Mereset kunci rahasia client dan mengembalikan nilai plaintext baru.

### B. Sisi Frontend React JS (Unified UI)
1. **`AppModules.tsx` (Pages):**
   Menyediakan satu antarmuka tunggal untuk mengelola modul aplikasi sekaligus kredensial SSO-nya:
   * **Tabel Terpadu:** Menampilkan No, Nama Modul, URL Callback, Client ID (dengan tombol salin cepat), dan Aksi.
   * **Aksi Tambahan:** Tombol Kunci (Key) untuk mereset kunci rahasia SSO.
   * **Modal Kredensial:** Menampilkan pop-up berisi Client ID dan Client Secret dalam bentuk plaintext pasca pembuatan atau reset kunci agar admin dapat menyalinnya sekali saja.
2. **`admin.ts` (API Services):**
   Menyediakan request terpadu `getAppModules()`, `createAppModule()`, `updateAppModule()`, `deleteAppModule()`, dan `resetAppModuleSecret()`.

---

## 🧪 5. VERIFICATION & TESTING RESULTS

Hasil pengujian yang diimplementasikan pada sistem untuk diletakkan di Bab IV:

### A. Black Box Testing (Tabel Uji)
Terdapat 18 Skenario Pengujian Fungsional (CRUD User, Role, Permission, Sync, dan Middleware Otorisasi) dengan status **100% Lulus**.
* *Skenario Penting:*
  1. Buat App Module baru -> Sistem otomatis membuat kredensial SSO dan mengembalikan status 201.
  2. Reset Secret App Module -> Client secret lama terhapus, client secret baru dibuat secara acak.
  3. Hapus App Module -> Data modul dan kredensial SSO terhapus secara berantai (*cascade*).

### B. Usability Testing (System Usability Scale - SUS)
* **Metodologi:** Kuesioner SUS dibagikan kepada 10 responden staff administrator.
* **Hasil Skor:** Rata-rata skor kontribusi responden dikalikan 2.5 menghasilkan nilai akhir **81.5**.
* **Interpretasi (Bangor et al., 2009):** Kategori **Excellent** (Grade B+), membuktikan antarmuka manajemen terpadu ini sangat efisien, mengurangi redundancy input, dan mudah dioperasikan.

### C. Performance Testing (Response Time)
Pengujian performa pada local development server (localhost):
1. **Autentikasi login & penerbitan token JWT:** Rata-rata 150 ms.
2. **Pengambilan data list AppModule beserta SSO Credentials:** Rata-rata 95 ms.
3. **Proses sinkronisasi (sync) permission ke role:** Rata-rata 210 ms.
4. **Validasi akses API di backend melalui Middleware:** Rata-rata 15 ms.

---

## ⚙️ 6. DEPLOYMENT & MAINTENANCE

1. **Backend Laravel Deployment:**
   * Instal dependency: `composer install`.
   * Setup `.env`: Koneksi database MySQL, generate token JWT (`php artisan jwt:secret`).
   * Jalankan migrasi dan seeder awal: `php artisan migrate:fresh --seed` (membuat tabel relasi baru, mendaftarkan default modules terikat 1-to-1 dengan sso-clients credentials).
   * Jalankan development server: `php artisan serve` (pada port 8000).
2. **Frontend React JS Deployment:**
   * Instal dependency: `npm install`.
   * Setup API base URL ke `http://127.0.0.1:8000/api`.
   * Jalankan development server: `npm run dev` (pada port 5173).
