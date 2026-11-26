<?php

namespace Database\Seeders;

use App\Enums\StockMovementType;
use App\Models\BoardColor;
use App\Models\Client;
use App\Models\GatedCommunity;
use App\Models\InstructionTemplate;
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

        User::factory()->carpintero()->create(['name' => 'Sergio Ruiz', 'email' => 'sergio@carpinteria.test']); // sin trabajos: figura libre

        $this->call(InstructionTemplateSeeder::class);
        $templates = InstructionTemplate::query()->active()->pluck('id');

        $stages = Stage::ordered();
        // [etapa (posición), días desde el inicio, plazo en días]
        $plan = [[2, 5, 30], [5, 20, 30], [8, 40, 45], [6, 32, 30]];
        foreach ($clientes->concat([$clientes[0]]) as $i => $cliente) {
            [$position, $since, $days] = $plan[$i];
            $order = WorkOrder::factory()->create([
                'client_id' => $cliente->id,
                'title' => ['Cocina completa con isla', 'Placard dormitorio principal', 'Vestidor en L', 'Mueble de TV flotante'][$i],
                'stage_id' => $stages->firstWhere('position', $position)->id,
                'started_on' => today()->subDays($since),
                'lead_time_days' => $days,
                'created_by' => $admin->id,
            ]);
            $order->addTasks($admin, $templates->shuffle()->take(3)->all());
            $order->carpenters()->attach($carpinteros[$i % 2]->id, ['is_lead' => true]);

            $carpintero = $carpinteros[$i % 2];
            $order->updates()->create(['user_id' => $carpintero->id, 'comment' => 'Arranqué con el trabajo, revisé medidas y materiales.', 'created_at' => now()->subDays(min($since, 3))->subHours($i)]);
            $order->updates()->create(['user_id' => $carpintero->id, 'from_stage_id' => $stages->firstWhere('position', $position - 1)->id, 'to_stage_id' => $order->stage_id, 'comment' => 'Etapa terminada.', 'created_at' => now()->subHours(2 + $i * 5)]);
        }

        // Un trabajo ya entregado y calificado por el cliente.
        $entregado = WorkOrder::factory()->create([
            'client_id' => $clientes[0]->id, 'title' => 'Biblioteca de living', 'stage_id' => $stages->last()->id,
            'started_on' => today()->subDays(70), 'lead_time_days' => 45, 'delivered_at' => today()->subDays(22), 'created_by' => $admin->id,
        ]);
        $entregado->carpenters()->attach($carpinteros[0]->id, ['is_lead' => true]);
        $entregado->rating()->create([
            'user_id' => $clienteUser->id, 'score_work' => 5, 'score_materials' => 5, 'score_installation' => 4, 'score_staff' => 5, 'score_service' => 4,
            'comment' => 'Quedó hermosa. La instalación se demoró un día, pero el equipo fue muy prolijo.',
        ]);

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
