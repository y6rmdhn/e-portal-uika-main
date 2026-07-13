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
    public function toArray($request): array
    {
        // Menggunakan dataValues jika Eloquent, atau langsung properti jika stdClass
        $role = $this->role ?? null;
        $unitId = $this->unit_id ?? null;

        // Ambil nama & code unit dengan aman
        $unitName = null;
        $unitCode = null;
        if (isset($this->unit)) {
            $unitName = is_array($this->unit) ? ($this->unit['nama_unit'] ?? null) : ($this->unit->nama_unit ?? null);
            $unitCode = is_array($this->unit) ? ($this->unit['code'] ?? null) : ($this->unit->code ?? null);
        }

        try {
            $payload = \Tymon\JWTAuth\Facades\JWTAuth::getPayload();
            if ($payload && $payload->get('is_scoped')) {
                if ($payload->get('role_name')) { $role = $payload->get('role_name'); }
                if ($payload->get('unit_id')) { $unitId = $payload->get('unit_id'); }
                if ($payload->get('unit_name')) { $unitName = $payload->get('unit_name'); }
                if ($payload->get('unit_code')) { $unitCode = $payload->get('unit_code'); }
            }
        } catch (\Exception $e) {}

        // Helper untuk format tanggal agar tidak crash jika properti berupa string biasa/null
        $formatDate = function($date) {
            if (!$date) return null;
            if ($date instanceof \Carbon\Carbon) return $date->toIso8601String();
            try { return \Carbon\Carbon::parse($date)->toIso8601String(); } catch(\Exception $e) { return null; }
        };

        return [
            'sso_id'             => $this->user_id ?? null,
            'name'               => $this->name ?? $this->email ?? 'User SSO', // Ambil name jika ada, fallback ke email
            'email'              => $this->email ?? null,
            'nidn'               => $this->nidn ?? null,
            'nip'                => $this->nip ?? null,
            'npm'                => $this->npm ?? null,
            'phone'              => $this->phone ?? null,
            'location'           => null,
            'image'              => $this->image ?? null,
            'is_active'          => (bool) ($this->isverified ?? true),
            'email_verified'     => (bool) ($this->isverified ?? true),
            'last_login_at'      => $formatDate($this->last_login_at ?? null),
            'institutional_role' => $role,
            'unit_id'            => $unitId,
            'unit_name'          => $unitName,
            'unit_code'          => $unitCode,
            'registered_at'      => $formatDate($this->created_at ?? null),
        ];
    }
}
