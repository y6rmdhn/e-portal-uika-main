<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel ini menyimpan daftar sub-aplikasi (SIAKAD, E-Library, dll.)
     * yang terdaftar dan diizinkan memanggil endpoint SSO introspection.
     *
     * Setiap sub-aplikasi wajib mengirim X-SSO-Client-ID dan X-SSO-Client-Secret
     * header saat memanggil /api/sso/introspect atau /api/sso/verify-access.
     */
    public function up(): void
    {
        Schema::create('sso_clients', function (Blueprint $table) {
            $table->id();

            // Nama aplikasi (human-readable)
            $table->string('name');

            // UUID unik sebagai client identifier (dikirim via header X-SSO-Client-ID)
            $table->uuid('client_id')->unique();

            // Hash dari client_secret (tidak pernah disimpan plaintext)
            $table->string('client_secret');

            // Modul yang boleh diakses oleh client ini (null = semua modul)
            // Format JSON: [1, 4, 5] = array of appModule_id
            $table->json('allowed_module_ids')->nullable();

            // URL callback resmi sub-aplikasi (whitelist)
            $table->string('callback_url')->nullable();

            // Deskripsi singkat tentang sub-aplikasi
            $table->text('description')->nullable();

            // Status aktif/nonaktif
            $table->boolean('is_active')->default(true);

            // Tracking penggunaan
            $table->timestamp('last_used_at')->nullable();
            $table->unsignedBigInteger('total_requests')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sso_clients');
    }
};
