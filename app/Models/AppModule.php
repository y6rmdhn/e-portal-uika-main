<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AppModule extends Authenticatable
{
    use HasFactory, SoftDeletes;


    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */

    protected $table = 'app_module';

    protected $fillable = [
        'name',
        'url',
        'icon',
    ];

    protected $appends = ['icon_url'];

    /**
     * Return full public URL for the icon, or null if not set.
     * Nginx di server cuma mem-proxy path di bawah `/eportal-api/` ke container backend ini
     * (lihat eportal-locations.conf). Path `/storage/...` tanpa prefix jatuh ke fallback SPA
     * frontend. Disamakan dengan publicAssetUrl di AboutUsController.
     */
    public function getIconUrlAttribute(): ?string
    {
        if (!$this->icon) {
            return null;
        }

        // Jika icon sudah berupa full URL (http:// atau https://)
        if (str_starts_with($this->icon, 'http://') || str_starts_with($this->icon, 'https://')) {
            if (!str_contains($this->icon, '/eportal-api/storage/') && str_contains($this->icon, '/storage/')) {
                return str_replace('/storage/', '/eportal-api/storage/', $this->icon);
            }
            return $this->icon;
        }

        $cleanPath = ltrim(preg_replace('#^storage/#', '', $this->icon), '/');
        $baseUrl = rtrim(config('app.url') ?: url('/'), '/');

        return $baseUrl . '/eportal-api/storage/' . $cleanPath;
    }

    public function permission()
    {
        return $this->hasMany(Permission::class, 'appModule_id', 'id');
    }

    public function ssoClient()
    {
        return $this->hasOne(SsoClient::class, 'app_module_id', 'id');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_app_modules', 'app_module_id', 'role_id');
    }


    public static function getTableColumns()
    {
        return DB::getSchemaBuilder()->getColumnListing('app_module');
    }
}
