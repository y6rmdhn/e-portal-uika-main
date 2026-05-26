<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sso_clients', function (Blueprint $table) {
            $table->foreignId('app_module_id')
                ->nullable()
                ->after('id')
                ->constrained('app_module')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sso_clients', function (Blueprint $table) {
            $table->dropForeign(['app_module_id']);
            $table->dropColumn('app_module_id');
        });
    }
};
