<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SsoIntegrationTemplate extends Model
{
    protected $table = 'sso_integration_templates';

    protected $fillable = [
        'name',
        'category',
        'language',
        'icon',
        'code_snippet',
        'description',
        'dependencies',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
