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
            // ── Identitas Utama ───────────────────────────────────────────────
            // Gunakan sso_id ini sebagai referensi di database sub-aplikasi Anda.
            // JANGAN simpan email/nidn sebagai primary key karena bisa berubah.
            'sso_id'   => $this->public_id,
            'name'     => $this->name,
            'email'    => $this->email,

            // ── Nomor Identitas Akademik (bisa null) ──────────────────────────
            'nidn' => $this->nidn,  // Nomor Induk Dosen Nasional
            'nip'  => $this->nip,   // Nomor Induk Pegawai
            'npm'  => $this->npm,   // Nomor Pokok Mahasiswa

            // ── Info Profil ───────────────────────────────────────────────────
            'phone'    => $this->phone,
            'location' => $this->location,
            'image'    => $this->image
                ? (filter_var($this->image, FILTER_VALIDATE_URL)
                    ? $this->image
                    : asset('storage/' . $this->image))
                : null,

            // ── Status Akun ───────────────────────────────────────────────────
            // is_active: false → user dinonaktifkan, sub-app HARUS menolak akses
            'is_active'      => (bool) $this->is_active,
            'email_verified' => !is_null($this->email_verified_at),
            'last_login_at'  => $this->last_login_at?->toIso8601String(),

            // ── Role Institusional (dari SSO) ─────────────────────────────────
            // Ini adalah role global di tingkat institusi (dosen, mahasiswa, staff).
            // Role KONTEKSTUAL (Kaprodi Prodi TI, Staff Prodi Manajemen) adalah
            // tanggung jawab sub-aplikasi masing-masing — simpan di DB lokal Anda.
            'institutional_role' => $this->getRoleNames()->first(),

            // ── Unit Kerja (dari SSO) ──────────────────────────────────────────
            'unit_id'            => $this->unit_id,
            'unit_name'          => $this->unit?->nama_unit,
            'unit_code'          => $this->unit?->code,

            // ── Timestamp ─────────────────────────────────────────────────────
            'registered_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
