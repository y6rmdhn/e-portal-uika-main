<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

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

    public function getJWTIdentifier()
    {
        return $this->getKey(); // user_id
    }

    public function getJWTCustomClaims()
    {
        return [
            'id'    => $this->user_id,
            'email' => $this->email,
            'role'  => $this->role,
            'nidn'  => $this->nidn,
            'npm'   => $this->npm,
        ];
    }
}