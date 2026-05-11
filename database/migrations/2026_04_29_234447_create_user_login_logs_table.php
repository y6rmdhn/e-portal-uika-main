<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_login_logs', function (Blueprint $table) {
            $table->id();
            // nullable karena login gagal belum tentu ada user_id yang diketahui
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->string('browser', 100)->nullable();
            $table->string('browser_version', 50)->nullable();
            $table->string('platform', 100)->nullable();
            $table->enum('device_type', ['desktop', 'mobile', 'tablet'])->default('desktop');
            $table->enum('status', ['success', 'failed'])->default('success');
            $table->string('failure_reason', 100)->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Index untuk query monitoring & cleanup
            $table->index(['user_id', 'created_at']);
            $table->index(['ip_address', 'status', 'created_at']); // untuk rate limiting
            $table->index('created_at');                            // untuk purge job
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_login_logs');
    }
};
