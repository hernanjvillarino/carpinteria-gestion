<?php

namespace Database\Factories;

use App\Enums\Priority;
use App\Models\Client;
use App\Models\Stage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkOrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'title' => fake()->randomElement(['Cocina completa', 'Placard dormitorio principal', 'Vestidor', 'Mueble de TV', 'Biblioteca']),
            'description' => 'Melamina blanca 18 mm, frentes con tapacanto de 2 mm y bisagras cierre suave.',
            'stage_id' => fn () => Stage::query()->orderBy('position')->value('id'),
            'priority' => Priority::Normal,
            'lead_time_days' => 45,
            'started_on' => now()->subDays(fake()->numberBetween(0, 20))->toDateString(),
            'created_by' => User::factory()->admin(),
        ];
    }
}
