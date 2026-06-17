<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserAdminResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'public_id'  => $this->public_id,
            'name'       => $this->name,
            'email'      => $this->email,
            'phone'      => $this->phone,
            'location'   => $this->location,
            'about_me'   => $this->about_me,
            'nidn'       => $this->nidn,
            'nip'        => $this->nip,
            'npm'        => $this->npm,
            'is_active'  => (bool) $this->is_active,
            'image'      => $this->image ? asset('storage/' . $this->image) : null,
            'roles'      => $this->roles->pluck('name')->toArray(),
            'unit_id'    => $this->unit_id,
            'unit'       => $this->unit ? [
                'id' => $this->unit->id,
                'code' => $this->unit->code,
                'nama_unit' => $this->unit->nama_unit,
            ] : null,
            'jabatan_units' => $this->roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'jabatan_id' => $role->id,
                    'nama_jabatan' => $role->name,
                    'unit_id' => $this->unit_id,
                    'code_unit' => $this->unit?->code,
                    'nama_unit' => $this->unit?->nama_unit,
                    'keterangan' => null,
                ];
            })->toArray(),
            'created_at' => $this->created_at?->format('d-m-Y H:i'),
            'updated_at' => $this->updated_at?->format('d-m-Y H:i'),
        ];
    }
}
