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
        $role = $this->role;
        $unitId = $this->unit_id;
        $unitName = $this->unit?->nama_unit;
        $unitCode = $this->unit?->code;

        try {
            $payload = \Tymon\JWTAuth\Facades\JWTAuth::getPayload();
            if ($payload && $payload->get('is_scoped')) {
                if ($payload->get('role_name')) {
                    $role = $payload->get('role_name');
                }
                if ($payload->get('unit_id')) {
                    $unitId = $payload->get('unit_id');
                }
                if ($payload->get('unit_name')) {
                    $unitName = $payload->get('unit_name');
                }
                if ($payload->get('unit_code')) {
                    $unitCode = $payload->get('unit_code');
                }
            }
        } catch (\Exception $e) {
        }

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
            'last_login_at' => $this->last_login_at ? \Carbon\Carbon::parse($this->last_login_at)->toIso8601String() : null,
            'institutional_role' => $role ?? null,
            'unit_id'            => $unitId,
            'unit_name'          => $unitName,
            'unit_code'          => $unitCode,
            'registered_at'      => $this->created_at ? $this->created_at->toIso8601String() : null,
        ];
    }
}
