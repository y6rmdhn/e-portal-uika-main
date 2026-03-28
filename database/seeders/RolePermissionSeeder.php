<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Permissions
        $modules = [
            'users'    => ['view', 'create', 'edit', 'delete'],
            'permissions'    => ['view', 'create', 'edit', 'delete'],
            'roles'    => ['view', 'create', 'edit', 'delete'], 
        ];

        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "{$module}.{$action}"]);
            }
        }

        // Roles
        $admin = Role::create(['name' => 'admin']);
        $user  = Role::create(['name' => 'user']);

        // Assign permissions
        $admin->givePermissionTo(Permission::all()); 
        // $admin->givePermissionTo(['manage users', 'view reports']);
        // $user->givePermissionTo(['view reports']);
    }
}
