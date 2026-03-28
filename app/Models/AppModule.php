<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB; 

class AppModule extends Authenticatable
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */

    protected $table = 'app_module';

    protected $fillable = [
        'name',
        'url' 
    ]; 

    public function permission()
    {
        return $this->hasMany(Permission::class, 'appModule_id', 'id'); 

    }

    public static function boot()
    {
        parent::boot();

        static::addGlobalScope(function ($query) {
            $query->whereNull('app_module.deleted_at');
        });
    }
    public static function getTableColumns()
    {
        return DB::getSchemaBuilder()->getColumnListing('app_module');
    } 
    
}
