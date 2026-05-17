<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // user yang melakukan aksi
            $table->unsignedBigInteger('actor_id')->nullable(); // siapa yang melakukan (bisa admin)
            $table->string('type'); // login, logout, update_profile, change_password, reset_password, app_access
            $table->string('description'); // deskripsi singkat
            $table->json('metadata')->nullable(); // data tambahan (ip, app name, dll)
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('actor_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_activity_logs');
    }
};
