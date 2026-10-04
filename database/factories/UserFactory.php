<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\User> */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nomor_induk' => (string) fake()->unique()->numerify('########'),
            'nama' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'aktif' => true,
        ];
    }
}
