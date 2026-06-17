# Product Requirement Document (PRD)
## Sistem Otorisasi Terpusat - Role-Based Access Control (RBAC)
### E-Portal Universitas Ibn Khaldun Bogor (UIKA)

---

## 📑 1. Identitas Dokumen & Informasi Sistem
* **Nama Sistem:** Modul Otorisasi Terpusat (RBAC) - E-Portal UIKA
* **Teknologi Backend:** Laravel 10.x, PHP 8.x, `spatie/laravel-permission` (Kustom), `tymon/jwt-auth`
* **Teknologi Frontend:** React JS, Vite, TypeScript, Tailwind CSS, Axios, Context API
* **Database:** MySQL 8.x
* **Penulis Dokumen:** Maulana Ikhsan (NPM: 221106043035)
* **Ruang Lingkup Dokumen:** Pengelolaan User, Role, Permission (Modular), serta Pemetaan Hak Akses (RBAC). **PENTING: Modul Single Sign-On (SSO) dikecualikan dari dokumen ini.**

---

## 🎯 2. Pendahuluan & Ringkasan Eksekutif
Aplikasi E-Portal UIKA adalah gerbang akses utama bagi sivitas akademika Universitas Ibn Khaldun Bogor. Untuk mengamankan data dan membatasi akses fitur secara tepat, sistem memerlukan mekanisme otorisasi yang fleksibel, granular, dan terpusat.

Mekanisme ini diselesaikan melalui implementasi **Contextual & Modular Role-Based Access Control (RBAC)**. Berbeda dengan RBAC tradisional yang memberikan hak akses secara global, sistem ini dirancang agar:
1. **Permission Berbasis Modul (Modular Scoped Permission)**: Hak akses (seperti `view`, `create`, `delete`) dikelompokkan secara ketat berdasarkan modul aplikasi (`app_module`).
2. **Role Kontekstual**: Peran pengguna (seperti Dosen, Mahasiswa, Staff) dipetakan ke dalam modul-modul spesifik melalui tabel transaksi otorisasi, sehingga hak akses pengguna dapat berbeda pada setiap aplikasi.
3. **Manajemen Terpadu**: Administrator dapat mengelola seluruh pengguna, peran, hak akses, dan hubungan relasinya melalui satu pintu panel admin E-Portal.

---

## 🏗️ 3. Arsitektur Teknis RBAC
Sistem ini menggunakan arsitektur **RESTful Client-Server** stateless yang memisahkan Frontend UI dan Backend API secara penuh.

### A. Alur Otorisasi Backend (Laravel)
1. **Otentikasi Stateless**: Menggunakan JWT (`tymon/jwt-auth`). Setiap request administratif ke API Backend wajib menyertakan JWT Bearer token di header.
2. **Pengecekan Role & Permission**: Menggunakan library `spatie/laravel-permission` yang dimodifikasi. Middleware `role:admin` digunakan untuk mengamankan seluruh API administratif di bawah prefix `/api/admins`.
3. **Modular Scope**: Model `Permission` dimodifikasi untuk menyimpan `appModule_id` guna melokalisasi hak akses ke modul tertentu.

### B. Alur Kontrol Akses Frontend (React JS)
1. **Proteksi Route**: React Router DOM dilindungi oleh Loader/Guard (`adminLoader`) yang memeriksa apakah role pengguna yang masuk adalah `admin`. Jika tidak, halaman diblokir secara total.
2. **State Global**: Menyimpan profil admin dan hak akses aktif di dalam global Context.
3. **UI Dinamis**: Komponen tombol dan navigasi disembunyikan/ditampilkan secara real-time berdasarkan daftar `permissions` yang dimuat.

---

## 🗄️ 4. Skema Basis Data (Data Model & ERD)

Sistem ini didukung oleh 7 tabel utama di database MySQL untuk mendukung fungsionalitas RBAC:

```
+------------------+          +--------------------+          +--------------------+
|      users       |          |  model_has_roles   |          |       roles        |
+------------------+          +--------------------+          +--------------------+
| id (PK)          |          | role_id (FK, PK)   |          | id (PK)            |
| public_id (UUID) |<---------| model_type (PK)    |--------->| name               |
| name             |          | model_id (FK, PK)  |          | guard_name         |
| email            |          +--------------------+          +--------------------+
| role_id (FK)     |                                                    ^
| is_active        |                                                    |
+------------------+                                                    |
         |                                                              |
         |                                                              |
         v                                                              |
+-------------------------------+                                       |
|  tx_user_module_permission    |                                       |
+-------------------------------+                                       |
| id (PK)                       |                                       |
| user_id (FK)                  |                                       |
| appModule_id (FK)             |                                       |
| role_id (FK) ---------------------------------------------------------+
| permission_id (FK) -----------+
+-------------------------------+
         |
         |
         v
+------------------+          +----------------------+          +--------------------+
|   permissions    |          | role_has_permissions |          |     app_module     |
+------------------+          +----------------------+          +--------------------+
| id (PK)          |<---------| permission_id (FK)   |          | id (PK)            |
| name             |          | role_id (FK)         |          | name               |
| guard_name       |          +----------------------+          | url                |
| appModule_id(FK) |-------------------------------------------->|                    |
+------------------+                                            +--------------------+
```

### Detail Tabel:
1. **`users`**: Menyimpan data identitas dasar user (NPM, NIDN, NIP), status keaktifan (`is_active`), dan global role (`role_id`).
2. **`roles`**: Menyimpan daftar peran global (misal: `admin`, `user`) dan peran fungsional sub-aplikasi (misal: `dosen`, `mahasiswa`).
3. **`permissions`**: Menyimpan data hak akses spesifik. Memiliki kolom tambahan `appModule_id` yang mereferensikan tabel `app_module`.
4. **`app_module`**: Menyimpan daftar aplikasi/modul yang terintegrasi (contoh: SIMPEG, SIAKAD).
5. **`role_has_permissions`**: Tabel pivot standard Spatie yang menghubungkan peran (`roles`) dengan hak akses (`permissions`).
6. **`model_has_roles`**: Menghubungkan user dengan peran global di tingkat aplikasi E-Portal.
7. **`tx_user_module_permission`**: Tabel transaksi pemetaan relasional dinamis yang mencatat user siapa, memiliki role apa, pada modul mana, dan dengan permission apa saja.

---

## 🎯 5. Kebutuhan Fungsional (Functional Requirements)

### 📌 FR-1: Manajemen Pengguna (User Management)
* **Deskripsi:** Admin harus dapat mengelola akun pengguna E-Portal.
* **Fitur Utama:**
  * **CRUD User**: Menambah, membaca, memperbarui, dan menghapus akun pengguna (Nama, Email, Password, NIP, NPM, NIDN, No HP, Foto Profil).
  * **Status Toggle**: Mengaktifkan atau menonaktifkan akun user secara instan.
  * **Reset Password**: Admin dapat melakukan paksa reset password user yang akan mengirimkan email notifikasi otomatis berisi password baru.
  * **Ekspor & Impor Excel**: Melakukan pencadangan data ke file `.xlsx` dan mempercepat input user secara massal melalui unggah template Excel.
  * **Audit Log**: Menampilkan riwayat aktivitas keamanan dan log masuk per pengguna.

### 📌 FR-2: Manajemen Peran (Role Management)
* **Deskripsi:** Mengelola tipe-tipe peran pengguna yang ada di dalam ekosistem universitas.
* **Fitur Utama:**
  * **CRUD Role**: Menambah nama role baru (misal: `dosen`, `mahasiswa`, `tendik`, `admin-fakultas`) dan menghapus role yang tidak terpakai.
  * **Assign/Unassign Global Role**: Melakukan penetapan role global ke user tertentu.

### 📌 FR-3: Manajemen Hak Akses Modular (Permission Management)
* **Deskripsi:** Mengelola kode fungsional hak akses yang diikat ke modul tertentu.
* **Fitur Utama:**
  * **CRUD Permission**: Menambah kode akses (misal: `simpeg.view`, `siakad.input_nilai`).
  * **Pengikatan Modul**: Setiap pembuatan permission wajib memilih modul aplikasi (`app_module`) yang menaunginya.
  * **Bulk Operations**: Fitur menyimpan, memperbarui, atau menghapus banyak permission sekaligus dalam satu aksi guna efisiensi administrasi developer.

### 📌 FR-4: Matriks Relasi Peran & Hak Akses (Role-Permission Mapping)
* **Deskripsi:** Menghubungkan hak akses (permissions) ke dalam suatu peran (role).
* **Fitur Utama:**
  * **Antarmuka Dua Kolom (Dual-Listbox UI)**: Menyediakan kolom kiri ("Belum Ditugaskan") dan kolom kanan ("Sudah Ditugaskan").
  * **Grouping Berbasis Modul**: Hak akses pada kolom kiri dan kanan dikelompokkan secara visual berdasarkan modul aplikasinya (misal: semua permission SIMPEG berkumpul dalam satu folder kartu).
  * **Assign & Unassign Massal**: Admin dapat mencentang banyak permission lintas modul, lalu mengklik tombol "Berikan Akses" atau "Cabut Akses" untuk memperbarui basis data.
  * **Pencarian Real-time**: Input pencarian independen di setiap kolom untuk menyaring permission secara instan.

---

## 🔒 6. Kebutuhan Non-Fungsional (Non-Functional Requirements)

### 📌 NFR-1: Keamanan (Security)
* **JWT Signing**: Setiap token otorisasi yang diterbitkan ditandatangani secara kriptografis menggunakan algoritma HS256 dengan secret key yang aman.
* **Data Masking & Hash**: Password pengguna disimpan menggunakan algoritma hashing `Bcrypt` (via Laravel `Hash::make()`).
* **Input Validation**: Validasi ketat di sisi backend untuk mencegah SQL Injection, Cross-Site Scripting (XSS), dan brute force login.
* **Audit Trail**: Pencatatan otomatis setiap aksi administratif (seperti reset password, perubahan role) ke dalam tabel `user_activity_logs`.

### 📌 NFR-2: Performa & Efisiensi (Performance)
* **Waktu Respons API**:
  * Autentikasi Admin & verifikasi token: `< 150 ms`.
  * Sinkronisasi relasi role-permission secara massal: `< 250 ms`.
  * Query list data pengguna dengan pagination: `< 100 ms`.
* **Database Optimization**: Penggunaan indeks pada kolom kunci pencarian (`user_id`, `role_id`, `appModule_id`, `permission_id`) di tabel transaksi.

### 📌 NFR-3: Pengalaman Pengguna (Usability)
* **Responsive Layout**: Antarmuka dashboard admin mendukung tampilan desktop, tablet, dan mobile.
* **Micro-Animations**: Transisi animasi halus saat membuka modal dialog, memuat data (spinner), dan memindahkan item hak akses untuk memberikan impresi premium.
* **Feedback System**: Notifikasi pop-up instan (Toast notifications) untuk setiap operasi sukses maupun gagal.

---

## 🧪 7. Hasil Pengujian Sistem (Verification)

Sistem otorisasi RBAC ini telah diuji secara komprehensif melalui tiga metode:

### A. Pengujian Fungsional (Black Box Testing)
* **Total Skenario Uji:** 18 skenario (CRUD Pengguna, CRUD Role, CRUD Permission, Pencarian, Validasi Duplikasi, Proteksi Middleware).
* **Hasil:** **100% Sukses**. Seluruh input tidak valid diblokir dengan respons error 422/400, dan fungsionalitas berjalan sesuai spesifikasi.

### B. Uji Kelayakan Pengguna (System Usability Scale - SUS)
* **Metode:** Pengujian dilakukan kepada 10 responden administrator internal UIKA menggunakan kuesioner standard SUS (10 pertanyaan skala Likert).
* **Hasil Skor:** Rata-rata skor SUS yang dicapai adalah **81.5**.
* **Kesimpulan:** Nilai 81.5 masuk dalam kategori **Excellent (Grade B+)**, menunjukkan sistem ini sangat mudah dipahami dan efisien digunakan untuk mengelola hak akses universitas yang kompleks.

### C. Pengujian Kecepatan Respons (Latency)
* **Membaca Profil & Hak Akses User:** ~15 ms (Sangat cepat karena menggunakan optimasi query).
* **Sinkronisasi Matriks Hak Akses (Bulk Sync):** ~210 ms.
* **Autentikasi Middleware Otorisasi:** ~15 ms.
