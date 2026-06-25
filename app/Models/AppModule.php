<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;

class AppModule extends Authenticatable
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */

    protected $table = 'app_module';

    protected $fillable = [
        'name',
        'url',
    ];

    public function permission()
    {
        return $this->hasMany(Permission::class, 'appModule_id', 'id');
    }

    public function ssoClient()
    {
        return $this->hasOne(SsoClient::class, 'app_module_id', 'id');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_app_modules', 'app_module_id', 'role_id');
    }


    public static function getTableColumns()
    {
        return DB::getSchemaBuilder()->getColumnListing('app_module');
    }

}
