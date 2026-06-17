<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SsoClient extends Model
{
    use HasFactory;

    protected $table = 'sso_clients';

    protected $fillable = [
        'app_module_id',
        'name',
        'client_id',
        'client_secret',
        'allowed_module_ids',
        'callback_url',
        'description',
        'is_active',
        'last_used_at',
        'total_requests',
    ];

    protected $hidden = [
        'client_secret',
    ];

    protected $casts = [
        'allowed_module_ids' => 'array',
        'is_active'          => 'boolean',
        'last_used_at'       => 'datetime',
    ];

    // ─── Factory Helper ───────────────────────────────────────────────────────

    /**
     * Generate pasangan client_id + client_secret baru.
     * Kembalikan plaintext secret (hanya tampil sekali saat pembuatan).
     */
    public static function generateCredentials(): array
    {
        $plainSecret = Str::random(64);

        return [
            'client_id'     => (string) Str::uuid(),
            'client_secret' => hash('sha256', $plainSecret),
            'plain_secret'  => $plainSecret,
        ];
    }

    // ─── Validation Helper ────────────────────────────────────────────────────

    /**
     * Cek apakah plaintext secret cocok dengan yang tersimpan.
     */
    public function verifySecret(string $plainSecret): bool
    {
        return hash('sha256', $plainSecret) === $this->client_secret;
    }

    /**
     * Cek apakah client ini punya akses ke appModule_id tertentu.
     * Jika allowed_module_ids null → boleh akses semua modul.
     */
    public function canAccessModule(int $appModuleId): bool
    {
        if (is_null($this->allowed_module_ids)) {
            return true;
        }

        return in_array($appModuleId, $this->allowed_module_ids);
    }

    // ─── Tracking ─────────────────────────────────────────────────────────────

    /**
     * Catat penggunaan terakhir client (fire-and-forget, tidak perlu await).
     */
    public function recordUsage(): void
    {
        $this->timestamps = false;
        $this->increment('total_requests');
        $this->update(['last_used_at' => now()]);
        $this->timestamps = true;
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function appModule()
    {
        return $this->belongsTo(AppModule::class, 'app_module_id');
    }
}
