<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create roles
        $adminRole     = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $userRole      = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        $dosenRole     = Role::firstOrCreate(['name' => 'dosen', 'guard_name' => 'web']);
        $mahasiswaRole = Role::firstOrCreate(['name' => 'mahasiswa', 'guard_name' => 'web']);

        // Create Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name'              => 'Administrator',
                'password'          => Hash::make('password'),
                'is_active'         => true,
                'phone'             => '081234567890',
                'email_verified_at' => now(),
                'role_id'           => $adminRole->id,
            ]
        );
        $admin->syncRoles([$adminRole]);

        // Create Dosen User
        $dosen = User::firstOrCreate(
            ['email' => 'dosen@gmail.com'],
            [
                'name'              => 'Dr. Ahmad Dosen, M.T.',
                'password'          => Hash::make('password'),
                'is_active'         => true,
                'phone'             => '081234567891',
                'email_verified_at' => now(),
                'role_id'           => $dosenRole->id,
            ]
        );
        $dosen->syncRoles([$dosenRole]);

        // Create Mahasiswa User
        $mahasiswa = User::firstOrCreate(
            ['email' => 'mahasiswa@gmail.com'],
            [
                'name'              => 'Budi Mahasiswa',
                'password'          => Hash::make('password'),
                'is_active'         => true,
                'phone'             => '081234567892',
                'email_verified_at' => now(),
                'role_id'           => $mahasiswaRole->id,
            ]
        );
        $mahasiswa->syncRoles([$mahasiswaRole]);

        // Create Regular User
        $user = User::firstOrCreate(
            ['email' => 'user@gmail.com'],
            [
                'name'              => 'User Biasa',
                'password'          => Hash::make('password'),
                'is_active'         => true,
                'phone'             => '081234567893',
                'email_verified_at' => now(),
                'role_id'           => $userRole->id,
            ]
        );
        $user->syncRoles([$userRole]);
    }
}
