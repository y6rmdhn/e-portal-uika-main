# Spesifikasi Diagram RBAC Lengkap (Bab IV Skripsi)
## E-Portal Universitas Ibn Khaldun Bogor (UIKA)

Dokumen ini berisi daftar lengkap diagram dengan format **Mermaid Script** untuk seluruh diagram Bab IV yang dirujuk dalam naskah skripsi `Skripsi_RBAC_Final_Updated.docx`. 

Semua diagram disesuaikan secara khusus untuk ruang lingkup (**Scope: RBAC Murni**) tanpa menyertakan alur SSO. Anda dapat menyalin (copy-paste) kode Mermaid di bawah ini ke editor pilihan Anda (misalnya [Mermaid Live Editor](https://mermaid.live)).

---

## 📊 DAFTAR DIAGRAM BAB IV

| No Gambar | Nama Diagram di Skripsi | Jenis Diagram | Status di Dokumen ini |
|---|---|---|---|
| **Gambar 4.1** | Diagram Sistem yang Sedang Berjalan | Flowchart | Ready (Mermaid) |
| **Gambar 4.2** | Diagram Sistem yang Diusulkan | Flowchart | Ready (Mermaid) |
| **Gambar 4.3** | Arsitektur Sistem RESTful Client-Server E-Portal UIKA | Block Diagram | Ready (Mermaid) |
| **Gambar 4.4** | Use Case Diagram Modul App Modules | Use Case | Ready (Mermaid) |
| **Gambar 4.5** | Use Case Diagram Modul Roles (Manajemen Peran) | Use Case | Ready (Mermaid) |
| **Gambar 4.6** | Use Case Diagram Modul Permissions (Manajemen Hak Akses) | Use Case | Ready (Mermaid) |
| **Gambar 4.7** | Use Case Diagram Modul Hak Akses (Role-Permission Mapping) | Use Case | Ready (Mermaid) |
| **Gambar 4.8** | Use Case Diagram Terintegrasi Sistem RBAC E-Portal UIKA | Use Case | Ready (Mermaid) |
| **Gambar 4.9** | Activity Diagram Login dan Otorisasi | Activity | Ready (Mermaid) |
| **Gambar 4.10** | Activity Diagram Manajemen Role | Activity | Ready (Mermaid) |
| **Gambar 4.11** | Activity Diagram Manajemen Permission | Activity | Ready (Mermaid) |
| **Gambar 4.12** | Class Diagram Modul RBAC | Class | Ready (Mermaid) |
| **Gambar 4.13** | Sequence Diagram Autentikasi dan Otorisasi | Sequence | Ready (Mermaid) |
| **Gambar 4.14** | Component Diagram Arsitektur RBAC | Component | Ready (Mermaid) |
| **Gambar 4.15** | Deployment Diagram Infrastruktur Sistem E-Portal UIKA | Deployment | Ready (Mermaid) |
| **Gambar 4.16** | ERD Basis Data RBAC | ERD | Ready (Mermaid) |

---

### Gambar 4.1: Diagram Sistem yang Sedang Berjalan (Flowchart)
Diagram ini memodelkan proses verifikasi hak akses di sistem lama yang masih terdesentralisasi, ad-hoc, dan tidak konsisten.

```mermaid
flowchart TD
    Start(["Mulai"]) --> Access["User ingin mengakses Fitur A di Aplikasi X"]
    Access --> CheckDb["Cek tabel user lokal atau hardcoded role di App X"]
    CheckDb -- Tidak Cocok --> Deny["Akses Ditolak"] --> End(["Selesai"])
    CheckDb -- Cocok --> Grant["Akses Diberikan"]
    
    Grant --> AccessY["User ingin mengakses Fitur B di Aplikasi Y"]
    AccessY --> CheckDbY["Cek tabel user lokal atau hardcoded role di App Y"]
    CheckDbY -- Tidak Cocok --> DenyY["Akses Ditolak"] --> End
    CheckDbY -- Cocok --> GrantY["Akses Diberikan"] --> End
```

---

### Gambar 4.2: Diagram Sistem yang Diusulkan (Flowchart)
Diagram ini memodelkan alur akses tersentralisasi menggunakan RBAC dan JWT Token yang divalidasi oleh E-Portal.

```mermaid
flowchart TD
    Start(["Mulai"]) --> Login["User login di E-Portal UIKA"]
    Login --> Auth{"Verifikasi JWT dan Role Global"}
    Auth -- Gagal --> Deny["Akses Ditolak atau Kembali ke Login"] --> End(["Selesai"])
    Auth -- Sukses --> Dash["Tampilkan Dashboard E-Portal dan Modul yang Diizinkan"]
    
    Dash --> ClickApp["User klik Modul Aplikasi - SIMPEG"]
    ClickApp --> Redirect["Backend generate Scoped Token dan Redirect ke App Callback"]
    Redirect --> LocalAuth["Aplikasi Penerima memverifikasi token ke E-Portal via API call_user"]
    LocalAuth --> CheckPerms{"Apakah user punya permission modul tersebut?"}
    CheckPerms -- Ya --> OpenApp["Masuk Dashboard Aplikasi Penerima dengan hak akses aktif"] --> End
    CheckPerms -- Tidak --> DenyApp["Akses Aplikasi Ditolak"] --> End
```

---

### Gambar 4.3: Arsitektur Sistem RESTful Client-Server E-Portal UIKA (Block Diagram)
Menampilkan pemisahan tanggung jawab antara Client-Side (React) dan Server-Side (Laravel API) serta interaksi dengan Database MySQL.

```mermaid
graph LR
    subgraph ClientNode ["Client Side - Vite dan React JS"]
        ReactApp["React UI Components - Tailwind, Lucide Icons"]
        AuthCtx["AuthContext - Session and Profile State"]
        Axios["Axios HTTP Client - Bearer Token Interceptor"]
        ReactApp --> AuthCtx
        AuthCtx --> Axios
    end

    subgraph ServerNode ["Server Side - Laravel REST API"]
        Router["API Routing and Middleware - jwt.verify, role admin"]
        Controllers["Controllers - User, Role, Permission"]
        Services["Services and Repositories - UserAdminService, ActivityLog"]
        Spatie["Spatie Permission Engine - HasRoles, HasPermissions"]
        
        Router --> Controllers
        Controllers --> Services
        Services --> Spatie
    end

    subgraph DbNode ["Database Side - MySQL"]
        MySQL["e_portal_db - users, roles, permissions, tx_user_module_permission"]
    end

    Axios -->|HTTP Requests atau JSON| Router
    Router -->|JSON Response| Axios
    Spatie -->|SQL Queries PDO| MySQL
```

---

### Gambar 4.4: Use Case Diagram Modul App Modules
Pemodelan fungsionalitas pengelolaan modul aplikasi oleh Administrator.

```mermaid
graph TD
    Admin((Administrator))

    subgraph ModulAppModules ["Modul App Modules - Management"]
        UC_ViewModule("Melihat Daftar Modul")
        UC_CreateModule("Menambah Modul Baru")
        UC_UpdateModule("Memperbarui Modul")
        UC_DeleteModule("Menghapus Modul")
    end

    Admin --> UC_ViewModule
    Admin --> UC_CreateModule
    Admin --> UC_UpdateModule
    Admin --> UC_DeleteModule
```

---

### Gambar 4.5: Use Case Diagram Modul Roles (Manajemen Peran)
Pemodelan fungsionalitas manajemen peran dan penetapan peran ke pengguna.

```mermaid
graph TD
    Admin((Administrator))

    subgraph ModulRoles ["Modul Roles - Management"]
        UC_ViewRole("Melihat Daftar Peran")
        UC_CreateRole("Menambah Peran Baru")
        UC_UpdateRole("Memperbarui Peran")
        UC_DeleteRole("Menghapus Peran")
        UC_AssignGlobalRole("Menugaskan Role Global ke User")
    end

    Admin --> UC_ViewRole
    Admin --> UC_CreateRole
    Admin --> UC_UpdateRole
    Admin --> UC_DeleteRole
    Admin --> UC_AssignGlobalRole
```

---

### Gambar 4.6: Use Case Diagram Modul Permissions (Manajemen Hak Akses)
Pemodelan fungsionalitas pengelolaan izin akses modular yang mencakup operasi massal (bulk).

```mermaid
graph TD
    Admin((Administrator))

    subgraph ModulPermissions ["Modul Permissions - Management"]
        UC_ViewPerm("Melihat Daftar Hak Akses")
        UC_CreatePerm("Menambah Hak Akses Baru")
        UC_UpdatePerm("Memperbarui Hak Akses")
        UC_DeletePerm("Menghapus Hak Akses")
        UC_BulkPerm("Bulk Operations Permission")
        
        UC_CreatePerm -.->|include| UC_SelectAppModule("Memilih Modul Aplikasi")
    end

    Admin --> UC_ViewPerm
    Admin --> UC_CreatePerm
    Admin --> UC_UpdatePerm
    Admin --> UC_DeletePerm
    Admin --> UC_BulkPerm
```

---

### Gambar 4.7: Use Case Diagram Modul Hak Akses (Role-Permission Mapping)
Pemodelan matriks relasional yang menyatukan permission dan role.

```mermaid
graph TD
    Admin((Administrator))

    subgraph ModulMatrix ["Modul Hak Akses - Role-Permission Mapping"]
        UC_SelectRole("Pilih Role Pengguna")
        UC_ViewMatrix("Melihat Matriks Pemetaan")
        UC_AssignUnassign("Assign/Unassign Permission ke Role")
        UC_SyncPerm("Sinkronisasi Massal Permission")
        
        UC_AssignUnassign -.->|include| UC_SelectRole
        UC_SyncPerm -.->|include| UC_SelectRole
        UC_ViewMatrix -.->|include| UC_SelectRole
    end

    Admin --> UC_SelectRole
    Admin --> UC_ViewMatrix
    Admin --> UC_AssignUnassign
    Admin --> UC_SyncPerm
```

---

### Gambar 4.8: Use Case Diagram Terintegrasi Sistem RBAC E-Portal UIKA
Diagram use case lengkap yang memetakan seluruh aktivitas aktor Administrator dan Pengguna Biasa.

```mermaid
graph TB
    Admin(("Administrator E-Portal"))
    User(("Pengguna biasa - Dosen atau Mhs atau Staff"))

    subgraph EPortalRBAC ["Sistem RBAC E-Portal UIKA"]
        UC_Login("Login dan Dapatkan Token JWT")
        UC_Profile("Mengakses dan Update Profil")
        UC_Dashboard("Mengakses Dashboard E-Portal")

        UC_M_App("Manajemen Modul Aplikasi CRUD")
        UC_M_Role("Manajemen Peran CRUD")
        UC_Assign_G("Menugaskan Role Global ke User")
        UC_M_Perm("Manajemen Hak Akses CRUD dan Bulk")
        UC_Sync_Matrix("Sinkronisasi Matriks Role-Permission")
    end

    User --> UC_Login
    User --> UC_Profile
    User --> UC_Dashboard

    Admin --> UC_Login
    Admin --> UC_Dashboard
    Admin --> UC_M_App
    Admin --> UC_M_Role
    Admin --> UC_Assign_G
    Admin --> UC_M_Perm
    Admin --> UC_Sync_Matrix

    UC_Dashboard -.->|includes| UC_Login
    UC_M_App -.->|includes| UC_Login
    UC_M_Role -.->|includes| UC_Login
    UC_M_Perm -.->|includes| UC_Login
    UC_Sync_Matrix -.->|includes| UC_Login
```

---

### Gambar 4.9: Activity Diagram Login dan Otorisasi (Activity)
Menggambarkan alur aktivitas proses login pengguna dan penerbitan JWT di sistem.

```mermaid
flowchart TD
    Start(["Mulai"]) --> Req["Pengguna mengirim Request API ke endpoint terproteksi"]
    Req --> CheckJWT{"Apakah Header Authorization memiliki Token JWT valid?"}
    
    CheckJWT -- Ya --> Decode["Decode payload Token JWT and Set User aktif"]
    CheckJWT -- Tidak --> Err401["Kirim Response 401 Unauthorized"] --> End(["Selesai"])
    
    Decode --> CheckAdminRoute{"Apakah route membutuhkan role khusus - role admin"}
    
    CheckAdminRoute -- Ya --> CheckAdminRole{"Apakah User memiliki role admin"}
    CheckAdminRoute -- Tidak --> Proceed["Teruskan request ke Controller"]
    
    CheckAdminRole -- Ya --> Proceed
    CheckAdminRole -- Tidak --> Err403["Kirim Response 403 Forbidden"] --> End
    
    Proceed --> Process["Controller memproses request"]
    Process --> Resp200["Kirim Response 200 OK dengan data"] --> End
```

---

### Gambar 4.10: Activity Diagram Manajemen Role (Activity)
Menggambarkan logika internal proses manipulasi data role oleh Admin.

```mermaid
flowchart TD
    Start(["Mulai"]) --> View["Admin melihat daftar role"]
    View --> Action{Pilih Aksi?}
    
    Action -- Tambah Role --> Input["Input Nama Role Baru"]
    Input --> Validate{"Apakah nama sudah terdaftar?"}
    Validate -- Ya --> Error["Tampilkan Error: Role sudah ada"] --> Input
    Validate -- Tidak --> Save["Simpan Role ke tabel roles"]
    
    Action -- Hapus Role --> Confirm{"Apakah yakin?"}
    Confirm -- Ya --> Delete["Hapus Role dan relasi pivot terkait"]
    Confirm -- Tidak --> View
    
    Action -- Edit Role --> InputEdit["Ubah Nama Role"]
    InputEdit --> Update["Update data di DB"]
    
    Save --> Success["Tampilkan Toast Sukses dan Refresh Data"]
    Delete --> Success
    Update --> Success
    Success --> End(["Selesai"])
```

---

### Gambar 4.11: Activity Diagram Manajemen Permission (Activity)
Alur proses CRUD permission modular, baik operasi tunggal maupun bulk.

```mermaid
flowchart TD
    Start(["Mulai"]) --> View["Admin membuka halaman Manajemen Permission"]
    View --> Action{Pilih Aksi?}
    
    Action -- Single Create --> InputSingle["Input nama permission dan pilih AppModule"]
    InputSingle --> SaveSingle["Simpan data di tabel permissions dengan appModule_id"]
    
    Action -- Bulk Operations --> SelectMode{Pilih Mode Bulk?}
    SelectMode -- Bulk Store --> InputBulk["Input beberapa permission sekaligus"]
    InputBulk --> SaveBulk["DB::transaction: Insert data secara massal"]
    
    SelectMode -- Bulk Delete --> CheckBulk["Pilih beberapa permission untuk dihapus"]
    CheckBulk --> DeleteBulk["DB::transaction: Delete data secara massal"]
    
    Action -- Edit Permission --> InputEdit["Ubah nama permission atau modul"]
    InputEdit --> Update["Update data di DB"]
    
    SaveSingle --> Success["Toast Sukses dan Refresh Data"]
    SaveBulk --> Success
    DeleteBulk --> Success
    Update --> Success
    Success --> End(["Selesai"])
```

---

### Gambar 4.12: Class Diagram Modul RBAC (Class Diagram)
Definisi model-model Laravel untuk otorisasi dan hubungannya dengan React JS Frontend.

```mermaid
classDiagram
    direction TB
    class User {
        +int id
        +string public_id
        +string name
        +string email
        +string password
        +int role_id
        +boolean is_active
        +getJWTCustomClaims()
        +role()
    }
    class Role {
        +int id
        +string name
        +string guard_name
    }
    class Permission {
        +int id
        +string name
        +string guard_name
        +int appModule_id
        +appModule()
    }
    class AppModule {
        +int id
        +string name
        +string url
        +permissions()
    }
    class TxUserModulPermission {
        +int id
        +int user_id
        +int appModule_id
        +int role_id
        +int permission_id
        +appModul()
        +role()
        +permission()
    }
    class AuthContext {
        +Object user
        +String token
        +login(credentials)
        +logout()
    }
    class ProtectedRoute {
        +Array allowedRoles
        +render()
    }

    User "1" --> "0..*" TxUserModulPermission : maps
    AppModule "1" --> "0..*" TxUserModulPermission : maps
    Role "1" --> "0..*" TxUserModulPermission : maps
    Permission "1" --> "0..*" TxUserModulPermission : maps

    AppModule "1" *-- "0..*" Permission : owns
    Role "0..*" o-- "0..*" Permission : role_has_permissions
    User "0..*" --> "1" Role : global_role
    ProtectedRoute ..> AuthContext : checks
```

---

### Gambar 4.13: Sequence Diagram Autentikasi dan Otorisasi (Sequence)
Menggambarkan interaksi run-time berurutan antara browser client, server routing, middleware, controller, dan database.

```mermaid
sequenceDiagram
    autonumber
    actor Browser as User Browser
    participant ReactFE as React JS Frontend
    participant Router as Laravel Routing
    participant JwtMiddleware as jwt.verify Middleware
    participant RoleMiddleware as role admin Middleware
    participant Controller as UserController
    participant DB as MySQL Database

    Note over Browser, DB: 1. Proses Autentikasi dan Penerbitan JWT
    Browser->>ReactFE: Input credentials - email dan password
    ReactFE->>Router: POST /api/auth/login
    activate Router
    Router->>DB: Cari user berdasarkan email
    DB-->>Router: Data user dan password_hash
    Router->>Router: Verifikasi password
    Router->>Router: Generate JWT Token - payload user_id, role
    Router-->>ReactFE: Response JSON { token, user_data }
    deactivate Router
    ReactFE->>ReactFE: Simpan JWT token di LocalStorage / State

    Note over Browser, DB: 2. Proses Otorisasi Akses Endpoint - Contoh: Get Users List
    Browser->>ReactFE: Navigasi ke halaman Manajemen Pengguna
    ReactFE->>Router: GET /api/admins/users - Header: Bearer token
    activate Router
    Router->>JwtMiddleware: Validasi Token
    activate JwtMiddleware
    alt [Token Tidak Valid atau Expired]
        JwtMiddleware-->>ReactFE: Response 401 Unauthorized
    else [Token Valid]
        JwtMiddleware->>RoleMiddleware: Teruskan Request
        deactivate JwtMiddleware
        activate RoleMiddleware
        alt [Pengguna bukan Admin]
            RoleMiddleware-->>ReactFE: Response 403 Forbidden
        else [Pengguna adalah Admin]
            RoleMiddleware->>Controller: Panggil method index()
            deactivate RoleMiddleware
            activate Controller
            Controller->>DB: SELECT * FROM users
            DB-->>Controller: Data pengguna
            Controller-->>ReactFE: Response 200 OK
            deactivate Controller
        end
    end
    deactivate Router
    ReactFE-->>Browser: Render halaman Manajemen Pengguna
```

---

### Gambar 4.14: Component Diagram Arsitektur RBAC (Component)
Menampilkan relasi dependensi komponen frontend React dan arsitektur backend Laravel.

```mermaid
flowchart TB
    subgraph Frontend ["Frontend Application - React JS"]
        AC["AuthContext"]
        PR["ProtectedRoute"]
        Pages["Pages - Role, Permission, User Manager"]
        Axios["Axios Client"]
    end

    subgraph Backend ["Backend API - Laravel"]
        Router["API Routing"]
        JwtM["jwt.verify Middleware"]
        RoleM["role admin Middleware"]
        Ctrl["Controllers - User, Role, Permission"]
        Spatie["Spatie HasRoles Trait"]
    end

    subgraph Database ["Relational Database - MySQL"]
        SpatieT["Spatie Tables"]
        CustomT["Custom Tables - app_module, tx_user"]
    end

    AC -->|provides token| Axios
    PR -->|checks state| AC
    Pages -->|restricted by| PR
    Pages -->|uses| Axios

    Axios -->|HTTP Requests atau JSON| Router
    Router --> JwtM
    JwtM --> RoleM
    RoleM --> Ctrl
    Ctrl --> Spatie
    Spatie -->|Eloquent Queries| SpatieT
    Ctrl -->|Eloquent Queries| CustomT
```

---

### Gambar 4.15: Deployment Diagram Infrastruktur Sistem E-Portal UIKA (Deployment)
Infrastruktur fisik runtime aplikasi saat proses development/development server local.

```mermaid
flowchart TD
    subgraph ClientNode ["Client Node - User Device"]
        Browser["Web Browser - Chrome atau Firefox"]
        ReactApp["React JS Application - SPA"]
        Browser --- ReactApp
    end

    subgraph ServerNode ["Application Server - Localhost"]
        subgraph ReactDev ["React Dev Server - Development Server"]
            ReactAppCode["Frontend Assets"]
        end
        subgraph LaravelDev ["Laravel Web Server - Development Port"]
            API["Laravel REST API"]
        end
    end

    subgraph DbNode ["Database Server - Port 3306"]
        DB["MySQL Database - e_portal_db"]
    end

    Browser -.->|Downloads Assets| ReactDev
    ReactApp -->|HTTP atau REST API JSON| API
    API -->|SQL Connection PDO| DB
```

---

### Gambar 4.16: ERD Basis Data RBAC (ERD)
Skema relasional basis data relasional MySQL untuk menyimpan Spatie Permission terintegrasi AppModule.

```mermaid
erDiagram
    users {
        bigint id PK
        varchar public_id UK
        varchar name
        varchar email UK
        varchar password
        bigint role_id FK
        varchar nidn
        varchar npm
        varchar nip
        boolean is_active
        timestamp last_login_at
    }
    roles {
        bigint id PK
        varchar name
        varchar guard_name
    }
    permissions {
        bigint id PK
        varchar name
        varchar guard_name
        bigint appModule_id FK
    }
    role_has_permissions {
        bigint role_id PK, FK
        bigint permission_id PK, FK
    }
    model_has_roles {
        bigint role_id PK, FK
        varchar model_type PK
        bigint model_id PK, FK
    }
    app_module {
        bigint id PK
        varchar name
        varchar url
    }
    tx_user_module_permission {
        bigint id PK
        bigint user_id FK
        bigint appModule_id FK
        bigint role_id FK
        bigint permission_id FK
    }

    users ||--o| roles : "has_one_global"
    permissions ||--o| app_module : "belongs_to_module"
    role_has_permissions }o--|| roles : ""
    role_has_permissions }o--|| permissions : ""
    model_has_roles }o--|| roles : ""
    model_has_roles }o--|| users : "assigns"
    tx_user_module_permission }o--|| users : ""
    tx_user_module_permission }o--|| app_module : ""
    tx_user_module_permission }o--|| roles : ""
    tx_user_module_permission }o--|| permissions : ""
```
