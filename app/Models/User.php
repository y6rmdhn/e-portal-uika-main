<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable, HasRoles;

    protected $connection  = 'ucl';
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
     * Virtual attribute for unit_id to maintain compatibility with single-unit code.
     */
    public function getUnitIdAttribute()
    {
        return $this->userJabatanUnits->first()?->unit_id;
    }

    /**
     * Virtual attribute for unit model to maintain compatibility with single-unit code.
     */
    public function getUnitAttribute()
    {
        return $this->userJabatanUnits->first()?->unit;
    }

    public function getJWTIdentifier()
    {
        return $this->getKey(); // user_id
    }

    public function getJWTCustomClaims()
    {
        return [
            'id'            => $this->user_id,
            'email'         => $this->email,
            'role'          => $this->role,
            'nidn'          => $this->nidn,
            'npm'           => $this->npm,
            'unit_id'       => $this->unit_id,
            'unit'          => $this->unit ? [
                'id'        => $this->unit->id,
                'code'      => $this->unit->code,
                'nama_unit' => $this->unit->nama_unit,
            ] : null,
            'jabatan_units' => $this->roles->map(function ($role) {
                return [
                    'id'           => $role->id,
                    'jabatan_id'   => $role->id,
                    'nama_jabatan' => $role->name,
                    'unit_id'      => $this->unit_id,
                    'code_unit'    => $this->unit?->code,
                    'nama_unit'    => $this->unit?->nama_unit,
                    'keterangan'   => null,
                ];
            })->toArray(),
        ];
    }
}