<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $roleTable = config('permission.table_names.roles', 'roles');
        DB::statement("
            CREATE OR REPLACE VIEW role_app_modules AS
            SELECT DISTINCT
                r.id AS role_id,
                am.id AS app_module_id
            FROM {$roleTable} r
            JOIN role_has_permissions rhp ON r.id = rhp.role_id
            JOIN permissions p ON rhp.permission_id = p.id
            JOIN app_module am ON p.appModule_id = am.id
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS role_app_modules");
    }
};
