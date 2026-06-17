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
        // 1. Add unit_id to users table
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_id')->nullable()->after('role_id');
            $table->foreign('unit_id')->references('id')->on('m_unit')->onDelete('set null');
        });

        // 2. Migrate existing user-unit relationships
        if (Schema::hasTable('trx_user_jabatan_unit')) {
            $assignments = DB::table('trx_user_jabatan_unit')
                ->whereNull('deleted_at')
                ->orderBy('created_at', 'asc')
                ->get();

            foreach ($assignments as $assignment) {
                DB::table('users')
                    ->where('public_id', $assignment->user_id)
                    ->update(['unit_id' => $assignment->unit_id]);
            }

            // 3. Drop pivot table
            Schema::dropIfExists('trx_user_jabatan_unit');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // One-way migration
    }
};
