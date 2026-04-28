<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IdleUsersResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id'            => $this->resource['id'],
            'name'          => $this->resource['name'],
            'email'         => $this->resource['email'],
            'role'          => $this->resource['role'],
            'last_login_at' => $this->resource['last_login_at'],
            'idle_days'     => $this->resource['idle_days'] ?? 'Belum pernah login',
        ];
    }
}
