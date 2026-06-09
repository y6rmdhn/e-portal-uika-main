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


    protected $connection = 'ucl';

    protected $table = 'tb_users';

    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $keyType = 'string';

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
        'last_login_at',
        'email_verified_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
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

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'id'    => $this->user_id,
            'email' => $this->email,
            'role'  => $this->role,
            'nidn'  => $this->nidn,
            'npm'   => $this->npm, // opsional jika ingin melempar role juga
        ];
    }
}
