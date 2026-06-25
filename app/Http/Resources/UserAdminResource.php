<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserAdminResource extends JsonResource
{
    public function toArray($request): array
    {
        // Safely get the unit — either from eager-loaded relation or via accessor
        $unit = $this->relationLoaded('unit')
            ? $this->getRelation('unit')
            : $this->unit()->first();

        return [
            'id'            => $this->user_id,
            'email'         => $this->email,
            'role'          => $this->role,
            'nidn'          => $this->nidn,
            'npm'           => $this->npm,
            'isverified'    => (bool) ($this->isverified ?? true),
            'unit_id'       => $unit?->id,
            'unit'          => $unit ? [
                'id'        => $unit->id,
                'code'      => $unit->code,
                'nama_unit' => $unit->nama_unit,
            ] : null,
            'roles'         => $this->roles->pluck('name')->toArray(),
            'jabatan_units' => $this->roles->map(function ($role) use ($unit) {
                return [
                    'id'           => $role->id,
                    'jabatan_id'   => $role->id,
                    'nama_jabatan' => $role->name,
                    'unit_id'      => $unit?->id,
                    'code_unit'    => $unit?->code,
                    'nama_unit'    => $unit?->nama_unit,
                    'keterangan'   => null,
                ];
            })->toArray(),
            'created_at'    => $this->created_at ? $this->created_at->format('d-m-Y H:i') : null,
            'updated_at'    => $this->updated_at ? $this->updated_at->format('d-m-Y H:i') : null,
        ];
    }
}