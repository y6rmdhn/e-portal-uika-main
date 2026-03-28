<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB; 

class Unit extends Authenticatable
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */

    protected $table = 'unit';

    protected $fillable = [
        'name',
        'description',
        'status' 
    ];  

    public static function boot()
    {
        parent::boot();

        static::addGlobalScope(function ($query) {
            $query->whereNull('unit.deleted_at');
        });
    }
    public static function getTableColumns()
    {
        return DB::getSchemaBuilder()->getColumnListing('unit');
    } 
    
}
