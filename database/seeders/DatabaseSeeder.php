<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            JabatanUnitSeeder::class,
            RolePermissionSeeder::class,
            UserSeeder::class,
            SsoClientSeeder::class,
            SsoIntegrationTemplateSeeder::class,
        ]);
    }
}
