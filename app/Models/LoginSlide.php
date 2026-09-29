<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginSlide extends Model
{
    protected $table = 'login_slides';

    protected $fillable = [
        'image',
        'title',
        'body',
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
