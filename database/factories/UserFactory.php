<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'remember_token' => Str::random(10),
            'nidn' => $this->faker->unique()->numerify('##########'),
            'nip' => $this->faker->unique()->numerify('##################'),
            'npm' => $this->faker->unique()->numerify('##########'),
            'phone' => $this->faker->unique()->numerify('08##########'),
            'location' => $this->faker->city(),
            'about_me' => $this->faker->sentence(),
            'is_active' => $this->faker->boolean(90),
            'image' => null,
            'role_id' => 1,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function unverified()
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => null,
            ];
        });
    }
}
