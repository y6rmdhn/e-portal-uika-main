<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->user_id,
            'email'      => $this->email,
            'role'       => $this->role,
            'nidn'       => $this->nidn,
            'npm'        => $this->npm,
            'isverified' => (bool) $this->isverified,
            'created_at' => $this->created_at?->format('d-m-Y H:i'),
        ];
    }
}