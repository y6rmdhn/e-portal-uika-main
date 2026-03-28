<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModelHasRole extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'role_id',
        'model_type',
        'model_id',
    ];


    public $incrementing = false;
    public $timestamps = false;


    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'model_id', 'id');
    }
}
