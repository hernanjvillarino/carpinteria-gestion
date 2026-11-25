<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class GatedCommunityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Barrio '.fake()->unique()->lastName(),
            'city' => fake()->randomElement(['Pilar', 'Escobar', 'Tigre', 'Canning', 'Hudson']),
            'access_notes' => 'Ingreso con DNI y ART vigente, de 8 a 17 h.',
            'show_on_website' => true,
        ];
    }
}
