<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\AppModule;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // App Modules setup
        $appModules = [
            'users' => [
                'name' => 'Manajemen User',
                'url' => '/admin/user-management',
            ],
            'roles' => [
                'name' => 'Roles',
                'url' => '/admin/roles',
            ],
            'permissions' => [
                'name' => 'Permissions',
                'url' => '/admin/permissions',
            ],
            'siakad' => [
                'name' => 'SIAKAD (Akademik)',
                'url' => 'http://localhost:5174/sso/callback',
            ],
            'elibrary' => [
                'name' => 'E-Library UIKA',
                'url' => 'http://localhost:8082/sso/callback',
            ],
            'finance' => [
                'name' => 'Portal Keuangan',
                'url' => 'http://localhost:8083/sso/callback',
            ],
        ];

        $moduleIds = [];
        foreach ($appModules as $key => $data) {
            $appMod = AppModule::firstOrCreate(
                ['name' => $data['name']],
                ['url' => $data['url']]
            );
            $moduleIds[$key] = $appMod->id;
        }

        // Permissions definitions grouped by module key
        $modules = [
            'users'       => ['view', 'create', 'edit', 'delete'],
            'permissions' => ['view', 'create', 'edit', 'delete'],
            'roles'       => ['view', 'create', 'edit', 'delete'],
            'siakad'      => ['view', 'input_nilai'],
            'elibrary'    => ['view', 'pinjam'],
            'finance'     => ['view', 'bayar'],
        ];

        foreach ($modules as $module => $actions) {
            $appModuleId = $moduleIds[$module] ?? 0;
            foreach ($actions as $action) {
                $perm = Permission::firstOrCreate(
                    ['name' => "{$module}.{$action}"],
                    [
                        'guard_name' => 'web',
                        'appModule_id' => $appModuleId,
                    ]
                );

                if ($perm->appModule_id == 0 || is_null($perm->appModule_id)) {
                    $perm->update(['appModule_id' => $appModuleId]);
                }
            }
        }

        // Define Roles with non-colliding IDs to prevent JabatanUnitSeeder from overwriting them
        $admin = Role::where('name', 'admin')->first();
        if (!$admin) {
            $admin = new Role();
            $admin->id = 100;
            $admin->name = 'admin';
            $admin->guard_name = 'web';
            $admin->save();
        }

        $user = Role::where('name', 'user')->first();
        if (!$user) {
            $user = new Role();
            $user->id = 101;
            $user->name = 'user';
            $user->guard_name = 'web';
            $user->save();
        }

        $dosen = Role::where('name', 'dosen')->first();
        if (!$dosen) {
            $dosen = new Role();
            $dosen->id = 21; // Match ID 21 in JabatanUnitSeeder
            $dosen->name = 'dosen';
            $dosen->guard_name = 'web';
            $dosen->save();
        }

        $mahasiswa = Role::where('name', 'mahasiswa')->first();
        if (!$mahasiswa) {
            $mahasiswa = new Role();
            $mahasiswa->id = 102;
            $mahasiswa->name = 'mahasiswa';
            $mahasiswa->guard_name = 'web';
            $mahasiswa->save();
        }

        // Sync permissions to roles
        // Admin gets all permissions
        $admin->syncPermissions(Permission::all());

        // Dosen permissions: siakad (view, input_nilai), elibrary (view)
        $dosenPermissions = Permission::whereIn('name', [
            'siakad.view',
            'siakad.input_nilai',
            'elibrary.view'
        ])->get();
        $dosen->syncPermissions($dosenPermissions);

        // Mahasiswa permissions: siakad (view), elibrary (view, pinjam), finance (view)
        $mahasiswaPermissions = Permission::whereIn('name', [
            'siakad.view',
            'elibrary.view',
            'elibrary.pinjam',
            'finance.view'
        ])->get();
        $mahasiswa->syncPermissions($mahasiswaPermissions);

        // User permissions: general access to siakad and elibrary view
        $userPermissions = Permission::whereIn('name', [
            'siakad.view',
            'elibrary.view'
        ])->get();
        $user->syncPermissions($userPermissions);
    }
}
