<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB; 
use Spatie\Permission\Models\Role;

class AppModule extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'app_module';

    protected $fillable = [
        'name', 'url', 'icon', 'description', 'is_active', 'order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'app_module_roles');
    }

    public function permission()
    {
        return $this->hasMany(Permission::class, 'appModule_id', 'id'); 
    }

    public static function getTableColumns()
    {
        return DB::getSchemaBuilder()->getColumnListing('app_module');
    } 
}