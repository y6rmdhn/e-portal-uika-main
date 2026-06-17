<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // No-op: Keep trx_user_jabatan_unit as the pivot table.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // One-way migration
    }
};
