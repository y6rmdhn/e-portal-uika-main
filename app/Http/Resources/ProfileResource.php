<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'name'      => $this->name,
            'email'     => $this->email,
            'phone'     => $this->phone,
            'location'  => $this->location,
            'about_me'  => $this->about_me,
            'nidn'      => $this->nidn,
            'nip'       => $this->nip,
            'npm'       => $this->npm,
            'is_active' => (bool) $this->is_active,
            'image'     => $this->image ? asset('storage/' . $this->image) : null,
            'role'      => $this->roles->first()?->name,
            'created_at' => $this->created_at?->format('d-m-Y H:i'),
        ];
    }
}
