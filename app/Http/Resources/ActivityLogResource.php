<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
{
    public function toArray($request): array
    {
        $typeLabels = [
            'login'           => 'Login',
            'logout'          => 'Logout',
            'update_profile'  => 'Update Profile',
            'change_password' => 'Ganti Password',
            'reset_password'  => 'Reset Password',
            'app_access'      => 'Akses Aplikasi',
        ];

        $typeColors = [
            'login'           => 'emerald',
            'logout'          => 'gray',
            'update_profile'  => 'blue',
            'change_password' => 'amber',
            'reset_password'  => 'rose',
            'app_access'      => 'purple',
        ];

        return [
            'id'               => $this->id,
            'type'             => $this->type,
            'type_label'       => $typeLabels[$this->type] ?? $this->type,
            'type_color'       => $typeColors[$this->type] ?? 'gray',
            'description'      => $this->description,
            'metadata'         => $this->metadata,
            'created_at'       => $this->created_at?->format('d-m-Y H:i'),
            'created_at_human' => $this->created_at?->diffForHumans(),
        ];
    }
}
