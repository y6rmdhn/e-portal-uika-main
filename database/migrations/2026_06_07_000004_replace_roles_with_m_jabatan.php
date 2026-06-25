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
        if (!Schema::hasTable('roles')) {
            // Old roles table does not exist. We are starting in a fresh environment where Spatie
            // roles table was configured as m_jabatan from the beginning.
            // Ensure m_jabatan has 'name' and 'guard_name' columns.
            Schema::table('m_jabatan', function (Blueprint $table) {
                if (!Schema::hasColumn('m_jabatan', 'nama_jabatan')) {
                    $table->string('nama_jabatan', 100)->nullable();
                }
                if (!Schema::hasColumn('m_jabatan', 'name')) {
                    $table->string('name', 100)->nullable();
                }
                if (!Schema::hasColumn('m_jabatan', 'guard_name')) {
                    $table->string('guard_name', 100)->default('web');
                }
            });

            // If name is null, copy nama_jabatan to name
            DB::table('m_jabatan')->whereNull('name')->update(['name' => DB::raw('nama_jabatan')]);

            // Enforce NOT NULL and UNIQUE on name
            Schema::table('m_jabatan', function (Blueprint $table) {
                try {
                    $table->string('name', 100)->nullable(false)->change();
                } catch (\Exception $e) {}
                try {
                    $table->unique('name');
                } catch (\Exception $e) {
                    // Already exists or not supported
                }
            });
            return;
        }

        // 1. Drop view role_app_modules which references roles table
        DB::statement("DROP VIEW IF EXISTS role_app_modules");

        // 2. Drop foreign key constraints pointing to roles table
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
        });

        if (Schema::hasTable('user_roles')) {
            Schema::table('user_roles', function (Blueprint $table) {
                $table->dropForeign(['role_id']);
            });
        }

        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->dropForeign('model_has_roles_role_id_foreign');
        });

        Schema::table('role_has_permissions', function (Blueprint $table) {
            $table->dropForeign('role_has_permissions_role_id_foreign');
        });

        // 3. Add name and guard_name to m_jabatan table
        Schema::table('m_jabatan', function (Blueprint $table) {
            $table->string('name', 100)->nullable();
            $table->string('guard_name', 100)->default('web');
        });

        // Copy existing nama_jabatan to name
        DB::table('m_jabatan')->update(['name' => DB::raw('nama_jabatan')]);

        // 4. Read all roles from old roles table and migrate them to m_jabatan
        if (Schema::hasTable('roles')) {
            $oldRoles = DB::table('roles')->get();
            foreach ($oldRoles as $oldRole) {
                // Find or create in m_jabatan
                $newJabatanId = DB::table('m_jabatan')->where('name', $oldRole->name)->value('id');
                if (!$newJabatanId) {
                    $newJabatanId = DB::table('m_jabatan')->insertGetId([
                        'name' => $oldRole->name,
                        'nama_jabatan' => $oldRole->name,
                        'guard_name' => $oldRole->guard_name ?? 'web',
                        'created_at' => $oldRole->created_at ?? now(),
                        'updated_at' => $oldRole->updated_at ?? now(),
                    ]);
                }

                // Update references in users table
                DB::table('users')->where('role_id', $oldRole->id)->update(['role_id' => $newJabatanId]);

                // Update references in user_roles table
                if (Schema::hasTable('user_roles')) {
                    DB::table('user_roles')->where('role_id', $oldRole->id)->update(['role_id' => $newJabatanId]);
                }

                // Update references in model_has_roles table
                DB::table('model_has_roles')->where('role_id', $oldRole->id)->update(['role_id' => $newJabatanId]);

                // Update references in role_has_permissions table
                DB::table('role_has_permissions')->where('role_id', $oldRole->id)->update(['role_id' => $newJabatanId]);
            }

            // 5. Drop the old roles table
            Schema::dropIfExists('roles');
        }

        // 6. Enforce NOT NULL and UNIQUE on m_jabatan.name
        Schema::table('m_jabatan', function (Blueprint $table) {
            $table->string('name', 100)->nullable(false)->change();
            $table->unique('name');
        });

        // 7. Re-add foreign key constraints pointing to m_jabatan
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('role_id')->references('id')->on('m_jabatan')->onDelete('cascade');
        });

        if (Schema::hasTable('user_roles')) {
            Schema::table('user_roles', function (Blueprint $table) {
                $table->foreign('role_id')->references('id')->on('m_jabatan')->onDelete('cascade');
            });
        }

        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->foreign('role_id')->references('id')->on('m_jabatan')->onDelete('cascade');
        });

        Schema::table('role_has_permissions', function (Blueprint $table) {
            $table->foreign('role_id')->references('id')->on('m_jabatan')->onDelete('cascade');
        });

        // 8. Recreate the role_app_modules view
        DB::statement("
            CREATE OR REPLACE VIEW role_app_modules AS
            SELECT DISTINCT
                r.id AS role_id,
                am.id AS app_module_id
            FROM m_jabatan r
            JOIN role_has_permissions rhp ON r.id = rhp.role_id
            JOIN permissions p ON rhp.permission_id = p.id
            JOIN app_module am ON p.\"appModule_id\" = am.id
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Down migration can be left empty as this is a one-way destructive migration for database restructure
    }
};
