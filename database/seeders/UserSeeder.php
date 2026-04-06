<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'yopanramadhan8@gmail.com',
            'password' => Hash::make('password'),
            'role_id' => 1,
            'is_active' => true,
            'phone' => '081293674531',
        ]);

        $admin->assignRole('admin');

        $users = User::factory()->count(20)->create([
            'password' => Hash::make('password'),
            'role_id' => 2,
            'is_active' => true,
        ]);

        foreach ($users as $user) {
            $user->assignRole('user');
        }
    }
}
