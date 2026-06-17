<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserAdminResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->user_id,
            'email'         => $this->email,
            'role'          => $this->role,
            'nidn'          => $this->nidn,
            'npm'           => $this->npm,
            'isverified'    => (bool) ($this->isverified ?? true),
            'unit_id'       => $this->unit_id,
            'unit'          => $this->unit ? [
                'id' => $this->unit->id,
                'code' => $this->unit->code,
                'nama_unit' => $this->unit->nama_unit,
            ] : null,
            'roles'         => $this->roles->pluck('name')->toArray(),
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
            'created_at'    => $this->created_at ? $this->created_at->format('d-m-Y H:i') : null,
            'updated_at'    => $this->updated_at ? $this->updated_at->format('d-m-Y H:i') : null,
        ];
    }
}