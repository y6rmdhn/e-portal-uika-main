<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;  

class TxUserModulPermission extends Authenticatable
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */

    protected $table = 'tx_user_module_permission';

    protected $fillable = [
        'user_id',
        'appModule_id', 
        'unit_id',
        'role_id'
    ];

    public function appModul()
    {
        return $this->belongsTo(AppModule::class, 'appModule_id', 'id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id', 'id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    public function roleHasPermission()
    {
        return $this->hasMany(RoleHasPermission::class, 'role_id', 'role_id');
    }
 

    public static function boot()
    {
        parent::boot();

        static::addGlobalScope(function ($query) {
            $query->whereNull('tx_user_module_permission.deleted_at');
        });
    }
    public static function getTableColumns()
    {
        return DB::getSchemaBuilder()->getColumnListing('tx_user_module_permission');
    } 
    
}
