<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleHasPermission extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'permission_id',
        'role_id',
    ];


    protected $primaryKey = 'role_permission_id'; 
    public $incrementing = false;
    public $timestamps = false;


    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }
}
