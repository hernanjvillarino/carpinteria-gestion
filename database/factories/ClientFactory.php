<?php

namespace Database\Factories;

use App\Models\GatedCommunity;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('11 ####-####'),
            'gated_community_id' => GatedCommunity::factory(),
            'lot' => (string) fake()->numberBetween(1, 600),
        ];
    }
}
