<?php

namespace Tests\Feature;

use App\Enums\QuoteStatus;
use App\Models\Client;
use App\Models\Material;
use App\Models\Quote;
use App\Models\User;
use App\Models\WorkOrder;
use App\Notifications\QuoteAnswered;
use App\Notifications\QuoteSentToClient;
use Database\Seeders\MaterialCategorySeeder;
use Database\Seeders\StageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class QuoteTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $clientUser;

    private WorkOrder $order;

    private Material $board;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([StageSeeder::class, MaterialCategorySeeder::class]);
        Notification::fake();
        $this->admin = User::factory()->admin()->create();
        $this->clientUser = User::factory()->cliente()->create();
        $this->order = WorkOrder::factory()->create(['client_id' => Client::factory()->create(['user_id' => $this->clientUser->id])->id]);
        $this->board = Material::factory()->create(['name' => 'Melamina Blanco 18 mm', 'unit' => 'placa']);
        $this->board->setPrice(100000);
    }

    private function draftWithItems(): Quote
    {
        $this->actingAs($this->admin)->post("/admin/trabajos/{$this->order->id}/presupuestos")->assertRedirect();
        $quote = Quote::sole();
        $this->post("/admin/presupuestos/{$quote->id}/items", ['material_id' => $this->board->id, 'qty' => 3])->assertRedirect()->assertSessionHasNoErrors();
        $this->post("/admin/presupuestos/{$quote->id}/items", ['description' => 'Flete', 'qty' => 1, 'unit' => 'viaje', 'unit_price' => 20000])->assertRedirect()->assertSessionHasNoErrors();
        $this->put("/admin/presupuestos/{$quote->id}", ['labor_cost' => 180000, 'margin_pct' => 30, 'tax_pct' => 21, 'valid_until' => today()->addDays(10)->toDateString()])->assertRedirect()->assertSessionHasNoErrors();

        return $quote->fresh('items');
    }

    public function test_totals_are_calculated_from_items_labor_margin_and_tax(): void
    {
        $quote = $this->draftWithItems();

        // (300.000 + 20.000 + 180.000) × 1,30 = 650.000 ; × 1,21 = 786.500
        $this->assertSame('650000.00', $quote->subtotal);
        $this->assertSame('786500.00', $quote->total);
        $this->assertSame('100000.00', $quote->items->first()->unit_price);
        $this->assertSame('Melamina Blanco 18 mm', $quote->items->first()->description);
    }

    public function test_price_changes_do_not_alter_existing_quote_but_new_version_uses_them(): void
    {
        $quote = $this->draftWithItems();
        $this->board->setPrice(120000);

        $this->assertSame('100000.00', $quote->fresh('items')->items->first()->unit_price);

        $this->post("/admin/trabajos/{$this->order->id}/presupuestos", ['copy' => '1'])->assertRedirect();
        $v2 = Quote::where('version', 2)->sole();
        $this->assertSame('120000.00', $v2->items->first()->unit_price);
        $this->assertSame('180000.00', $v2->labor_cost);
        $this->assertSame(QuoteStatus::Borrador, $v2->status);
    }

    public function test_sent_quote_is_locked_and_client_accepts_it(): void
    {
        $quote = $this->draftWithItems();
        $this->post("/admin/presupuestos/{$quote->id}/enviar")->assertRedirect();
        Notification::assertSentTo($this->clientUser, QuoteSentToClient::class);

        $this->post("/admin/presupuestos/{$quote->id}/items", ['description' => 'Extra', 'qty' => 1, 'unit' => 'u', 'unit_price' => 1])
            ->assertSessionHasErrors('quote');

        $this->actingAs($this->clientUser)->get("/mi-cuenta/pedidos/{$this->order->id}")
            ->assertSee('Presupuesto para revisar')->assertSee('$ 786.500,00')
            ->assertDontSee('$ 100.000,00'); // el cliente no ve precios unitarios

        $this->actingAs($this->clientUser)->post("/mi-cuenta/presupuestos/{$quote->id}/respuesta", ['decision' => 'aceptar'])
            ->assertSessionHasNoErrors();

        $quote->refresh();
        $this->assertSame(QuoteStatus::Aceptado, $quote->status);
        $this->assertNotNull($quote->accepted_at);
        Notification::assertSentTo($this->admin, QuoteAnswered::class);
    }

    public function test_sending_a_new_version_supersedes_the_previous_one(): void
    {
        $v1 = $this->draftWithItems();
        $this->post("/admin/presupuestos/{$v1->id}/enviar");
        $this->post("/admin/trabajos/{$this->order->id}/presupuestos", ['copy' => '1']);
        $v2 = Quote::where('version', 2)->sole();
        $this->post("/admin/presupuestos/{$v2->id}/enviar");

        $this->assertSame(QuoteStatus::Vencido, $v1->fresh()->status);
        $this->actingAs($this->clientUser)->post("/mi-cuenta/presupuestos/{$v1->id}/respuesta", ['decision' => 'aceptar'])
            ->assertSessionHasErrors('quote');
    }

    public function test_expired_quote_cannot_be_accepted(): void
    {
        $quote = $this->draftWithItems();
        $this->post("/admin/presupuestos/{$quote->id}/enviar");
        $quote->update(['valid_until' => today()->subDay()]);

        $this->actingAs($this->clientUser)->post("/mi-cuenta/presupuestos/{$quote->id}/respuesta", ['decision' => 'aceptar'])
            ->assertSessionHasErrors('quote');
    }

    public function test_client_cannot_see_drafts_or_other_clients_quotes(): void
    {
        $quote = $this->draftWithItems();
        $this->actingAs($this->clientUser)->get("/mi-cuenta/presupuestos/{$quote->id}/pdf")->assertNotFound();

        $this->actingAs($this->admin)->post("/admin/presupuestos/{$quote->id}/enviar");
        $stranger = User::factory()->cliente()->create();
        Client::factory()->create(['user_id' => $stranger->id]);
        $this->actingAs($stranger)->get("/mi-cuenta/presupuestos/{$quote->id}/pdf")->assertForbidden();

        $this->actingAs($this->clientUser)->get("/mi-cuenta/presupuestos/{$quote->id}/pdf")->assertOk()->assertDownload();
    }

    public function test_admin_pages_and_internal_pdf_render(): void
    {
        $quote = $this->draftWithItems();
        $this->get("/admin/presupuestos/{$quote->id}")->assertOk()->assertSee('$ 786.500,00');
        $this->get("/admin/presupuestos/{$quote->id}/pdf")->assertOk()->assertDownload();
        $this->get("/admin/trabajos/{$this->order->id}")->assertOk()->assertSee('Presupuestos');
    }
}
