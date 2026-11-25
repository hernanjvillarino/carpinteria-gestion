<?php

namespace Database\Seeders;

use App\Enums\StockMovementType;
use App\Models\BoardColor;
use App\Models\Client;
use App\Models\GatedCommunity;
use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\Stage;
use App\Models\Supplier;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([StageSeeder::class, BoardColorSeeder::class, MaterialCategorySeeder::class]);

        if (! app()->environment('local', 'testing')) {
            return; // en producción solo se cargan las etapas
        }

        $admin = User::factory()->admin()->create(['name' => 'Administración', 'email' => 'admin@carpinteria.test']);
        $carpinteros = collect([
            User::factory()->carpintero()->create(['name' => 'Juan Pérez', 'email' => 'juan@carpinteria.test']),
            User::factory()->carpintero()->create(['name' => 'Martín Gómez', 'email' => 'martin@carpinteria.test']),
        ]);

        $barrios = GatedCommunity::factory()->count(3)->create();
        $clienteUser = User::factory()->cliente()->create(['name' => 'Laura Fernández', 'email' => 'cliente@carpinteria.test']);
        $clientes = collect([
            Client::factory()->create(['name' => 'Laura Fernández', 'email' => 'cliente@carpinteria.test', 'user_id' => $clienteUser->id, 'gated_community_id' => $barrios[0]->id]),
            Client::factory()->create(['gated_community_id' => $barrios[1]->id]),
            Client::factory()->create(['gated_community_id' => null, 'address' => 'Av. Libertador 1234, San Isidro']),
        ]);

        $stages = Stage::ordered();
        foreach ($clientes as $i => $cliente) {
            $order = WorkOrder::factory()->create([
                'client_id' => $cliente->id,
                'stage_id' => $stages[min($i * 3 + 1, $stages->count() - 1)]->id,
                'created_by' => $admin->id,
            ]);
            $order->carpenters()->attach($carpinteros[$i % 2]->id, ['is_lead' => true]);
        }

        $this->seedStock($admin);
    }

    private function seedStock(User $admin): void
    {
        $cat = MaterialCategory::pluck('id', 'name');
        $color = BoardColor::pluck('id', 'name');
        $proveedor = Supplier::create(['name' => 'Maderera del Norte', 'phone' => '11 4444-5555']);

        $items = [
            ['Melamina Blanco 18 mm', 'Placas', 'placa', 98000, 12, 4, ['corteopt_material' => 'melamina', 'thickness_mm' => 18, 'board_color_id' => $color['Blanco'], 'brand' => 'Faplac', 'sheet_length_mm' => 2750, 'sheet_width_mm' => 1830]],
            ['Melamina Roble Dakar 18 mm', 'Placas', 'placa', 135000, 3, 4, ['corteopt_material' => 'melamina', 'thickness_mm' => 18, 'board_color_id' => $color['Roble Dakar'], 'brand' => 'Egger', 'has_grain' => true, 'sheet_length_mm' => 2800, 'sheet_width_mm' => 2070]],
            ['Fibrofácil 3 mm Blanco', 'Placas', 'placa', 28000, 8, 2, ['corteopt_material' => 'hardboard', 'thickness_mm' => 3, 'board_color_id' => $color['Blanco']]],
            ['Canto ABS Blanco 22 x 0,45 mm', 'Cantos', 'm', 450, 300, 100, []],
            ['Bisagra cazoleta cierre suave', 'Herrajes', 'unidad', 4200, 40, 20, []],
            ['Guía telescópica 45 cm', 'Herrajes', 'unidad', 9800, 6, 10, []],
            ['Tornillo 4 x 50', 'Insumos', 'unidad', 35, 800, 200, []],
        ];
        foreach ($items as [$name, $category, $unit, $price, $stock, $min, $extra]) {
            $material = Material::create(['name' => $name, 'material_category_id' => $cat[$category], 'supplier_id' => $proveedor->id, 'unit' => $unit, 'min_stock' => $min] + $extra);
            $material->setPrice($price, $admin);
            $material->move(StockMovementType::Ingreso, $stock, $admin, note: 'Stock inicial');
        }
    }
}
