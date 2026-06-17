<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\UserJabatanUnit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create Spatie roles (saved locally in m_jabatan)
        // Note: Role IDs are aligned with RolePermissionSeeder to avoid collision.
        $adminRole     = Role::where('name', 'admin')->first();
        $userRole      = Role::where('name', 'user')->first();
        $dosenRole     = Role::where('name', 'dosen')->first();
        $mahasiswaRole = Role::where('name', 'mahasiswa')->first();

        // 1. Create/Retrieve Admin User on UCL connection (tb_users table)
        $admin = User::where('email', 'admin@gmail.com')->first();
        if (!$admin) {
            $admin = User::create([
                'user_id'    => (string) Str::uuid(),
                'email'      => 'admin@gmail.com',
                'password'   => Hash::make('password'),
                'role'       => 'admin',
                'isverified' => true,
            ]);
        }
        $admin->syncRoles([$adminRole]);

        // 2. Create/Retrieve Dosen User on UCL connection
        $dosen = User::where('email', 'dosen@gmail.com')->first();
        if (!$dosen) {
            $dosen = User::create([
                'user_id'    => (string) Str::uuid(),
                'email'      => 'dosen@gmail.com',
                'password'   => Hash::make('password'),
                'role'       => 'dosen',
                'nidn'       => '0407029202', // Example NIDN
                'isverified' => true,
            ]);
        }
        $dosen->syncRoles([$dosenRole]);

        // 3. Create/Retrieve Mahasiswa User on UCL connection
        $mahasiswa = User::where('email', 'mahasiswa@gmail.com')->first();
        if (!$mahasiswa) {
            $mahasiswa = User::create([
                'user_id'    => (string) Str::uuid(),
                'email'      => 'mahasiswa@gmail.com',
                'password'   => Hash::make('password'),
                'role'       => 'mahasiswa',
                'npm'        => '241106041778', // Example NPM
                'isverified' => true,
            ]);
        }
        $mahasiswa->syncRoles([$mahasiswaRole]);

        // 4. Create/Retrieve Regular User on UCL connection
        $user = User::where('email', 'user@gmail.com')->first();
        if (!$user) {
            $user = User::create([
                'user_id'    => (string) Str::uuid(),
                'email'      => 'user@gmail.com',
                'password'   => Hash::make('password'),
                'role'       => 'user',
                'isverified' => true,
            ]);
        }
        $user->syncRoles([$userRole]);

        // Reset and Seed local unit & jabatan assignments (trx_user_jabatan_unit in MySQL)
        UserJabatanUnit::whereIn('user_id', [
            $admin->user_id,
            $dosen->user_id,
            $mahasiswa->user_id,
            $user->user_id,
        ])->delete();

        UserJabatanUnit::create([
            'user_id'    => $admin->user_id,
            'jabatan_id' => $adminRole->id,
            'unit_id'    => 3, // Univ
            'keterangan' => 'Seeded administrator',
        ]);

        UserJabatanUnit::create([
            'user_id'    => $dosen->user_id,
            'jabatan_id' => $dosenRole->id,
            'unit_id'    => 1, // Teknik Informatika
            'keterangan' => 'Seeded dosen',
        ]);

        UserJabatanUnit::create([
            'user_id'    => $mahasiswa->user_id,
            'jabatan_id' => $mahasiswaRole->id,
            'unit_id'    => 1, // Teknik Informatika
            'keterangan' => 'Seeded mahasiswa',
        ]);

        UserJabatanUnit::create([
            'user_id'    => $user->user_id,
            'jabatan_id' => $userRole->id,
            'unit_id'    => 1, // Teknik Informatika
            'keterangan' => 'Seeded regular user',
        ]);
    }
}
