<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tb_users', function (Blueprint $table) {
            $table->timestamp('last_login_at')->nullable();
            $table->index('last_login_at');
        });
    }

    public function down(): void
    {
        Schema::table('tb_users', function (Blueprint $table) {
            $table->dropIndex(['last_login_at']);
            $table->dropColumn('last_login_at');
        });
    }
};
