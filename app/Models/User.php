<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable, HasRoles;

    protected $connection  = 'pgsql';
    protected $table       = 'tb_users';
    protected $primaryKey  = 'user_id';
    public    $incrementing = false;
    protected $keyType     = 'string';

    protected $fillable = [
        'user_id',
        'email',
        'password',
        'role',
        'nidn',
        'npm',
        'isverified',
        'role_id',
        'department_code',
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * Relationship to local E-Portal unit & jabatan mappings.
     */
    public function userJabatanUnits()
    {
        return $this->hasMany(UserJabatanUnit::class, 'user_id', 'user_id');
    }

    /**
     * Relation to unit via department_code.
     */
    public function unit()
    {
        return $this->belongsTo(Unit::class, 'department_code', 'code');
    }

    /**
     * Relation to role via role_id.
     */
    public function roleModel()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    public function getJWTIdentifier()
    {
        return $this->getKey(); // user_id
    }

    public function getJWTCustomClaims()
    {
        // Use getRelation to avoid conflict with the unit() relationship method
        $unit = $this->relationLoaded('unit') ? $this->getRelation('unit') : $this->unit()->first();

        return [
            'id'            => $this->user_id,
            'email'         => $this->email,
            'role'          => $this->role,
            'nidn'          => $this->nidn,
            'npm'           => $this->npm,
            'unit_id'       => $unit?->id,
            'unit'          => $unit ? [
                'id'        => $unit->id,
                'code'      => $unit->code,
                'nama_unit' => $unit->nama_unit,
            ] : null,
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
        ];
    }
}
