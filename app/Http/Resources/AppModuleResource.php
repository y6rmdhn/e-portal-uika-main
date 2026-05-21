<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AppModuleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'url'         => $this->url,
            'icon'        => $this->icon ? asset('storage/' . $this->icon) : null,
            'description' => $this->description,
            'is_active'   => $this->is_active,
            'order'       => $this->order,
            'roles'       => $this->whenLoaded('roles', fn() =>
                $this->roles->pluck('name') // ['admin', 'mahasiswa', 'dosen']
            ),
        ];
    }
}