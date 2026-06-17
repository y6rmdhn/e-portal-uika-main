<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * SsoUserResource
 *
 * Format data user yang distandarisasi untuk dikonsumsi sub-aplikasi.
 */
class SsoUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'sso_id'             => $this->user_id,
            'name'               => $this->email,
            'email'              => $this->email,
            'nidn'               => $this->nidn ?? null,
            'nip'                => null,
            'npm'                => $this->npm ?? null,
            'phone'              => null,
            'location'           => null,
            'image'              => null,
            'is_active'          => (bool) ($this->isverified ?? true),
            'email_verified'     => (bool) ($this->isverified ?? true),
            'last_login_at'      => $this->last_login_at ? $this->last_login_at->toIso8601String() : null,
            'institutional_role' => $this->role ?? null,
            'unit_id'            => $this->unit_id,
            'unit_name'          => $this->unit?->nama_unit,
            'unit_code'          => $this->unit?->code,
            'registered_at'      => $this->created_at ? $this->created_at->toIso8601String() : null,
        ];
    }
}
