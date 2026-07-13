<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
{
    public function toArray($request): array
    {
        $typeLabels = [
            // Auth
            'login'           => 'Login',
            'logout'          => 'Logout',
            // Profile
            'update_profile'  => 'Update Profile',
            'change_password' => 'Ganti Password',
            'reset_password'  => 'Reset Password',
            // App
            'app_access'      => 'Akses Aplikasi',
            // Unit CRUD
            'unit_create'     => 'Buat Unit',
            'unit_update'     => 'Update Unit',
            'unit_delete'     => 'Hapus Unit',
            // Unit assignments
            'unit_assign'     => 'Tugaskan Unit',
            'unit_unassign'   => 'Cabut Unit',
            // Permission assignments
            'permission_assign'   => 'Tugaskan Hak Akses',
            'permission_unassign' => 'Cabut Hak Akses',
            'permission_sync'     => 'Sinkron Hak Akses',
            // Role CRUD
            'role_create'     => 'Buat Role',
            'role_update'     => 'Update Role',
            'role_delete'     => 'Hapus Role',
            // Role assignments
            'role_assign'     => 'Tugaskan Role',
            'role_unassign'   => 'Cabut Role',
        ];

        $typeColors = [
            // Auth
            'login'           => 'emerald',
            'logout'          => 'gray',
            // Profile
            'update_profile'  => 'blue',
            'change_password' => 'amber',
            'reset_password'  => 'rose',
            // App
            'app_access'      => 'purple',
            // Unit CRUD
            'unit_create'     => 'teal',
            'unit_update'     => 'sky',
            'unit_delete'     => 'rose',
            // Unit assignments
            'unit_assign'     => 'emerald',
            'unit_unassign'   => 'rose',
            // Permission assignments
            'permission_assign'   => 'emerald',
            'permission_unassign' => 'rose',
            'permission_sync'     => 'purple',
            // Role CRUD
            'role_create'     => 'teal',
            'role_update'     => 'sky',
            'role_delete'     => 'rose',
            // Role assignments
            'role_assign'     => 'emerald',
            'role_unassign'   => 'rose',
        ];

        $typeCategories = [
            'login'           => 'auth',
            'logout'          => 'auth',
            'update_profile'  => 'profile',
            'change_password' => 'profile',
            'reset_password'  => 'profile',
            'app_access'      => 'system',
            'unit_create'     => 'data',
            'unit_update'     => 'data',
            'unit_delete'     => 'data',
            'unit_assign'     => 'data',
            'unit_unassign'   => 'data',
            // Permission assignments
            'permission_assign'   => 'data',
            'permission_unassign' => 'data',
            'permission_sync'     => 'data',
            // Role CRUD
            'role_create'     => 'data',
            'role_update'     => 'data',
            'role_delete'     => 'data',
            // Role assignments
            'role_assign'     => 'data',
            'role_unassign'   => 'data',
        ];

        return [
            'id'               => $this->id,
            'type'             => $this->type,
            'type_label'       => $typeLabels[$this->type] ?? $this->type,
            'type_color'       => $typeColors[$this->type] ?? 'gray',
            'type_category'    => $typeCategories[$this->type] ?? 'other',
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
