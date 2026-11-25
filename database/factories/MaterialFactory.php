<?php

namespace Database\Factories;

use App\Enums\MaterialUnit;
use App\Models\MaterialCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class MaterialFactory extends Factory
{
    public function definition(): array
    {
        return [
            'material_category_id' => fn () => MaterialCategory::firstOrCreate(['name' => 'Insumos'])->id,
            'name' => 'Tornillo '.fake()->unique()->numerify('#x##'),
            'unit' => MaterialUnit::Unidad,
            'min_stock' => 0,
            'is_active' => true,
        ];
    }
}
