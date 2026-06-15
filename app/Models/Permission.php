<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use HasFactory;

    protected $fillable = [
        'name',
        'guard_name',
        'appModule_id',
    ];

    public function appModule()
    {
        return $this->belongsTo(AppModule::class, 'appModule_id', 'id');
    }
}
