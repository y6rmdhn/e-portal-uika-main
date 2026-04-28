<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // JADIKAN SEPERTI INI:
        Role::firstOrCreate(['name' => 'mahasiswa']);
        Role::firstOrCreate(['name' => 'dosen']);

        // Role::create(['name' => 'super-admin']);
        // Role::create(['name' => 'admin-siakad']);

        $admin = User::factory()->create([
            'name'              => 'Yopan Ramadhan',
            'email'             => 'yopandev11@gmail.com',
            'password'          => Hash::make('password'),
            'is_active'         => true,
            'phone'             => '081293674531',
            'email_verified_at' => now(),
        ]);

        $admin->assignRole('admin');

        $users = User::factory()->count(20)->create([
            'password'          => Hash::make('password'),
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);

        foreach ($users as $user) {
            $user->assignRole('mahasiswa');
        }
    }
}
