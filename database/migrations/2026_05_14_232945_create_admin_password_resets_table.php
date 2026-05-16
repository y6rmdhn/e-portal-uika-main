<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_password_resets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete(); // admin yang request
            $table->string('user_email');         // email user yang di-reset
            $table->string('pending_password');   // password baru (encrypted)
            $table->string('token')->unique();    // token verifikasi
            $table->timestamp('expires_at');      // expired 24 jam
            $table->timestamp('verified_at')->nullable(); // kapan diklik
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_password_resets');
    }
};