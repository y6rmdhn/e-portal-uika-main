<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * SsoUserResource
 *
 * Format data user yang distandarisasi untuk dikonsumsi sub-aplikasi.
 *
 * PENTING:
 * - Menggunakan `sso_id` (public_id/UUID) bukan internal ID integer.
 * - Tidak mengekspos password, remember_token, atau data sensitif lainnya.
 * - Sub-aplikasi HARUS menyimpan `sso_id` ini sebagai foreign key ke user mereka.
 */
class SsoUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'sso_id'             => $this->user_id,
            'name'               => $this->email, // tb_users gak punya name
            'email'              => $this->email,
            'nidn'               => $this->nidn ?? null,
            'nip'                => null,
            'npm'                => $this->npm ?? null,
            'phone'              => null,
            'location'           => null,
            'image'              => null,
            'is_active'          => (bool) ($this->isverified ?? true),
            'email_verified'     => (bool) ($this->isverified ?? true),
            'last_login_at'      => null,
            'institutional_role' => $this->role ?? null,
            'registered_at'      => null,
        ];
    }
}
