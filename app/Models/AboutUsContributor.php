<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AboutUsContributor extends Model
{
    protected $table = 'about_us_contributors';

    protected $fillable = [
        'app_module_id',
        'name',
        'type',
        'angkatan',
        'contribution',
        'photo',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function appModule()
    {
        return $this->belongsTo(AppModule::class, 'app_module_id', 'id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
