<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements JWTSubject, MustVerifyEmail
{
    use HasFactory, Notifiable, HasRoles, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'email',
        'nidn',
        'nip',
        'npm',
        'password',
        'phone',
        'location',
        'about_me',
        'is_active',
        'image',
        "role_id",
        'unit_id',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'id',
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
    ];

    public function uniqueIds()
    {
        return ['public_id'];
    }

    public function getRouteKeyName()
    {
        return 'public_id';
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'id' => $this->public_id,
            'email' => $this->email,
            'role' => $this->role,
            'unit_id' => $this->unit_id,
            'unit' => $this->unit ? [
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
        ];
    }
}
