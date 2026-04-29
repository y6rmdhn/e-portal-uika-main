<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginLog extends Model
{
    public $timestamps = false;

    protected $table = 'user_login_logs';

    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'browser',
        'browser_version',
        'platform',
        'device_type',     // desktop | mobile | tablet
        'status',          // success | failed
        'failure_reason',  // invalid_password | account_inactive | dll
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ─── Boot ─────────────────────────────────────────────────────────────────

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = now();
        });
    }
}
