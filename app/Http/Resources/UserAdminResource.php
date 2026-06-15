<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserAdminResource extends JsonResource
{
    public function toArray($request): array
    {
        // Handle both stdClass (dari UCL) dan Eloquent model
        $data = is_object($this->resource) ? (array) $this->resource : $this->resource;

        return [
            'id'         => $data['user_id'] ?? null,
            'email'      => $data['email']   ?? null,
            'role'       => $data['role']    ?? null,
            'nidn'       => $data['nidn']    ?? null,
            'npm'        => $data['npm']     ?? null,
            'isverified' => $data['isverified'] ?? false,
            'created_at' => $data['created_at'] ?? null,
        ];
    }
}