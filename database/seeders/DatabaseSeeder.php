<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\GatedCommunity;
use App\Models\Stage;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([StageSeeder::class, BoardColorSeeder::class]);

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
    }
}
