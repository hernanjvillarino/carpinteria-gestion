<?php

namespace Database\Seeders;

use App\Models\MaterialCategory;
use Illuminate\Database\Seeder;

class MaterialCategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Placas', 'Cantos', 'Herrajes', 'Insumos', 'Terminaciones'] as $name) {
            MaterialCategory::firstOrCreate(['name' => $name]);
        }
    }
}
