<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AboutUsSetting extends Model
{
    protected $table = 'about_us_settings';

    protected $fillable = [
        'title',
        'description',
        'banner_photo',
    ];

    /**
     * Baris pengaturan selalu tunggal — buat kalau belum ada, supaya
     * controller tidak perlu menangani kasus "belum pernah diisi".
     */
    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1], ['title' => 'Tentang Kami']);
    }
}
