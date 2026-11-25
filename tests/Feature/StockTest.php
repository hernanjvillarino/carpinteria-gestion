<?php

namespace Tests\Feature;

use App\Enums\StockMovementType;
use App\Models\Material;
use App\Models\User;
use App\Models\WorkOrder;
use Database\Seeders\BoardColorSeeder;
use Database\Seeders\MaterialCategorySeeder;
use Database\Seeders\StageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([StageSeeder::class, BoardColorSeeder::class, MaterialCategorySeeder::class]);
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_creates_board_with_price_and_initial_stock(): void
    {
        $this->actingAs($this->admin)->post('/admin/stock', [
            'name' => 'Melamina Blanco 18 mm',
            'material_category_id' => 1,
            'unit' => 'placa',
            'price' => 98000,
            'min_stock' => 4,
            'initial_stock' => 10,
            'corteopt_material' => 'melamina',
            'thickness_mm' => 18,
            'board_color_id' => 1,
        ])->assertRedirect()->assertRedirect()->assertSessionHasNoErrors();

        $m = Material::sole();
        $this->assertSame('98000.00', $m->current_price);
        $this->assertSame('10.000', $m->stock_qty);
        $this->assertSame(1, $m->prices()->count());
        $this->assertSame(1, $m->movements()->count());
    }

    public function test_board_requires_thickness_and_color(): void
    {
        $this->actingAs($this->admin)->post('/admin/stock', [
            'name' => 'Placa', 'material_category_id' => 1, 'unit' => 'placa', 'price' => 1, 'min_stock' => 0,
            'corteopt_material' => 'mdf',
        ])->assertSessionHasErrors(['thickness_mm', 'board_color_id']);
    }

    public function test_price_change_is_kept_in_history(): void
    {
        $m = Material::factory()->create();
        $m->setPrice(100, $this->admin);

        $this->actingAs($this->admin)->put("/admin/stock/{$m->id}", [
            'name' => $m->name, 'material_category_id' => $m->material_category_id, 'unit' => 'unidad',
            'price' => 130, 'min_stock' => 0,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('130.00', $m->fresh()->current_price);
        $this->assertSame(['130.00', '100.00'], $m->prices()->pluck('price')->all());
    }

    public function test_movements_update_stock_and_flag_minimum(): void
    {
        $m = Material::factory()->create(['min_stock' => 5]);
        $m->move(StockMovementType::Ingreso, 10, $this->admin);
        $m->move(StockMovementType::Consumo, 7, $this->admin);
        $m->move(StockMovementType::Ajuste, -1, $this->admin, note: 'Rotura');

        $this->assertSame('2.000', $m->fresh()->stock_qty);
        $this->assertTrue($m->fresh()->isBelowMinimum());
        $this->assertSame(['-1.000', '-7.000', '10.000'], $m->movements()->pluck('qty')->all());
        $this->actingAs($this->admin)->get('/admin')->assertSee('Insumos bajo el mínimo')->assertSee($m->name);
    }

    public function test_assigned_carpenter_registers_consumption(): void
    {
        $juan = User::factory()->carpintero()->create();
        $order = WorkOrder::factory()->create();
        $order->carpenters()->attach($juan);
        $m = Material::factory()->create();
        $m->setPrice(50);
        $m->move(StockMovementType::Ingreso, 100, $this->admin);

        $this->actingAs($juan)->post("/trabajos/{$order->id}/consumos", ['material_id' => $m->id, 'qty' => 12])
            ->assertSessionHasNoErrors();

        $mv = $order->stockMovements()->sole();
        $this->assertSame('-12.000', $mv->qty);
        $this->assertSame('50.00', $mv->unit_cost);
        $this->assertSame('88.000', $m->fresh()->stock_qty);

        $other = WorkOrder::factory()->create();
        $this->actingAs($juan)->post("/trabajos/{$other->id}/consumos", ['material_id' => $m->id, 'qty' => 1])->assertForbidden();
        $this->actingAs($juan)->get('/admin/stock')->assertForbidden();
    }

    public function test_stock_pages_render(): void
    {
        $m = Material::factory()->create();
        $m->setPrice(10);
        $this->actingAs($this->admin);
        foreach (['/admin/stock', '/admin/stock/create', "/admin/stock/{$m->id}", "/admin/stock/{$m->id}/edit", '/admin/stock/proveedores'] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
