<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserJabatanUnitSyncSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mappings = DB::table('trx_user_jabatan_unit')->get();
        $this->command->info("Syncing " . $mappings->count() . " user-jabatan mappings...");

        $synced = 0;
        foreach ($mappings as $map) {
            // Check if the user exists in tb_users
            $userExists = DB::table('tb_users')->where('user_id', $map->user_id)->exists();
            if (!$userExists) {
                continue;
            }

            // Check if role/jabatan exists in m_jabatan
            $roleExists = DB::table('m_jabatan')->where('id', $map->jabatan_id)->exists();
            if (!$roleExists) {
                continue;
            }

            // Sync role_id and department_code in tb_users table
            $unit = DB::table('m_unit')->where('id', $map->unit_id)->first();
            $deptCode = $unit ? $unit->code : null;

            $jabatan = DB::table('m_jabatan')->where('id', $map->jabatan_id)->first();
            $roleName = $jabatan ? $jabatan->name : null;

            DB::table('tb_users')
                ->where('user_id', $map->user_id)
                ->where(function ($query) {
                    $query->whereNull('role_id')
                          ->orWhereNull('department_code');
                })
                ->update([
                    'role_id' => $map->jabatan_id,
                    'department_code' => $deptCode,
                    'role' => $roleName,
                ]);

            // Check if already assigned in model_has_roles
            $exists = DB::table('model_has_roles')
                ->where('role_id', $map->jabatan_id)
                ->where('model_type', 'App\Models\User')
                ->where('model_id', $map->user_id)
                ->exists();

            if (!$exists) {
                DB::table('model_has_roles')->insert([
                    'role_id' => $map->jabatan_id,
                    'model_type' => 'App\Models\User',
                    'model_id' => $map->user_id,
                ]);
                $synced++;
            }
        }

        $this->command->info("Successfully synced $synced new mappings to model_has_roles.");
    }
}
