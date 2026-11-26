<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'dni' => (string) fake()->numberBetween(20000000, 45000000),
            'phone' => fake()->phoneNumber(),
            'is_active' => true,
        ];
    }
}
