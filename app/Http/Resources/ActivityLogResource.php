<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
{
    /**
     * Satu tabel untuk semua metadata tipe aktivitas.
     *
     * Sebelumnya label, warna, dan kategori disimpan di tiga array terpisah
     * sehingga gampang tidak sinkron saat tipe baru ditambahkan. Sekarang
     * satu baris per tipe: [label, warna, kategori, aksi].
     *
     * Kolom "aksi" dipakai frontend untuk menandai operasi CRUD.
     */
    private const TYPE_META = [
        // ── Autentikasi ─────────────────────────────────────────────────────
        'login'                => ['Login',                'emerald', 'auth',    'auth'],
        'logout'               => ['Logout',               'gray',    'auth',    'auth'],

        // ── Profil ──────────────────────────────────────────────────────────
        'update_profile'       => ['Update Profil',        'blue',    'profile', 'update'],
        'change_password'      => ['Ganti Password',       'amber',   'profile', 'update'],
        'reset_password'       => ['Reset Password',       'rose',    'profile', 'update'],

        // ── Akses aplikasi ──────────────────────────────────────────────────
        'app_access'           => ['Akses Aplikasi',       'purple',  'system',  'access'],

        // ── User CRUD ───────────────────────────────────────────────────────
        'user_create'          => ['Buat User',            'teal',    'data',    'create'],
        'user_update'          => ['Update User',          'sky',     'data',    'update'],
        'user_delete'          => ['Hapus User',           'rose',    'data',    'delete'],
        'user_toggle_active'   => ['Ubah Status Akun',     'amber',   'data',    'update'],

        // ── Unit CRUD ───────────────────────────────────────────────────────
        'unit_create'          => ['Buat Unit',            'teal',    'data',    'create'],
        'unit_update'          => ['Update Unit',          'sky',     'data',    'update'],
        'unit_delete'          => ['Hapus Unit',           'rose',    'data',    'delete'],
        'unit_assign'          => ['Tugaskan Unit',        'emerald', 'data',    'update'],
        'unit_unassign'        => ['Cabut Unit',           'rose',    'data',    'update'],

        // ── Role CRUD ───────────────────────────────────────────────────────
        'role_create'          => ['Buat Role',            'teal',    'data',    'create'],
        'role_update'          => ['Update Role',          'sky',     'data',    'update'],
        'role_delete'          => ['Hapus Role',           'rose',    'data',    'delete'],
        'role_assign'          => ['Tugaskan Role',        'emerald', 'data',    'update'],
        'role_unassign'        => ['Cabut Role',           'rose',    'data',    'update'],

        // ── Permission CRUD ─────────────────────────────────────────────────
        'permission_create'    => ['Buat Permission',      'teal',    'data',    'create'],
        'permission_update'    => ['Update Permission',    'sky',     'data',    'update'],
        'permission_delete'    => ['Hapus Permission',     'rose',    'data',    'delete'],
        'permission_assign'    => ['Tugaskan Hak Akses',   'emerald', 'data',    'update'],
        'permission_unassign'  => ['Cabut Hak Akses',      'rose',    'data',    'update'],
        'permission_sync'      => ['Sinkron Hak Akses',    'purple',  'data',    'update'],

        // ── Modul aplikasi ──────────────────────────────────────────────────
        'app_module_create'    => ['Buat Modul',           'teal',    'data',    'create'],
        'app_module_update'    => ['Update Modul',         'sky',     'data',    'update'],
        'app_module_delete'    => ['Hapus Modul',          'rose',    'data',    'delete'],
        'sso_secret_reset'     => ['Reset SSO Secret',     'amber',   'system',  'update'],
    ];

    /**
     * Daftar tipe untuk mengisi dropdown filter di frontend, sudah
     * dikelompokkan per kategori. Dipakai ActivityLogController.
     */
    public static function typeOptions(): array
    {
        $out = [];
        foreach (self::TYPE_META as $type => [$label, $color, $category, $action]) {
            $out[] = [
                'value'    => $type,
                'label'    => $label,
                'category' => $category,
                'action'   => $action,
                'color'    => $color,
            ];
        }
        return $out;
    }

    public function toArray($request): array
    {
        [$label, $color, $category, $action] = self::TYPE_META[$this->type]
            ?? [$this->type, 'gray', 'other', 'other'];

        return [
            'id'               => $this->id,
            'type'             => $this->type,
            'type_label'       => $label,
            'type_color'       => $color,
            'type_category'    => $category,
            'type_action'      => $action,
            'description'      => $this->description,
            'metadata'         => $this->metadata,
            'actor'            => $this->actor ? [
                'id'    => $this->actor->user_id,
                'name'  => $this->actor->email,
                'email' => $this->actor->email,
            ] : null,
            'created_at'       => $this->created_at?->format('d-m-Y H:i'),
            'created_at_human' => $this->created_at?->diffForHumans(),
        ];
    }
}
