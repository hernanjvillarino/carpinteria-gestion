<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Stage;
use App\Models\User;
use App\Models\WorkOrder;
use Database\Seeders\StageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorkOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(StageSeeder::class);
        Storage::fake('local');
    }

    private function stage(int $position): Stage
    {
        return Stage::where('position', $position)->firstOrFail();
    }

    public function test_admin_creates_a_work_order_and_assigns_carpenters(): void
    {
        $admin = User::factory()->admin()->create();
        $juan = User::factory()->carpintero()->create();
        $martin = User::factory()->carpintero()->create();
        $client = Client::factory()->create();

        $this->actingAs($admin)->post('/admin/trabajos', [
            'client_id' => $client->id,
            'title' => 'Cocina completa',
            'priority' => 'alta',
            'carpinteros' => [$juan->id, $martin->id],
            'lead_id' => $martin->id,
        ])->assertRedirect();

        $order = WorkOrder::sole();
        $this->assertSame('T-'.now()->year.'-0001', $order->code);
        $this->assertSame($this->stage(1)->id, $order->stage_id);
        $this->assertEqualsCanonicalizing([$juan->id, $martin->id], $order->carpenters->pluck('id')->all());
        $this->assertTrue((bool) $order->carpenters->firstWhere('id', $martin->id)->pivot->is_lead);
    }

    public function test_codes_are_sequential(): void
    {
        $first = WorkOrder::factory()->create();
        $second = WorkOrder::factory()->create();

        $this->assertSame('T-'.now()->year.'-0002', $second->code);
        $this->assertNotSame($first->code, $second->code);
    }

    public function test_carpenter_only_sees_assigned_work_orders(): void
    {
        $juan = User::factory()->carpintero()->create();
        $mine = WorkOrder::factory()->create(['title' => 'Vestidor de Juan']);
        $other = WorkOrder::factory()->create(['title' => 'Placard de otro']);
        $mine->carpenters()->attach($juan);

        $this->actingAs($juan)->get('/taller')->assertSee('Vestidor de Juan')->assertDontSee('Placard de otro');
        $this->actingAs($juan)->get("/taller/trabajos/{$mine->id}")->assertOk();
        $this->actingAs($juan)->get("/taller/trabajos/{$other->id}")->assertForbidden();
        $this->actingAs($juan)->post("/trabajos/{$other->id}/avances", ['comment' => 'hola'])->assertForbidden();
    }

    public function test_carpenter_advances_to_next_stage_with_photos(): void
    {
        $juan = User::factory()->carpintero()->create();
        $order = WorkOrder::factory()->create(['stage_id' => $this->stage(7)->id]); // Corte
        $order->carpenters()->attach($juan);

        $this->actingAs($juan)->post("/trabajos/{$order->id}/avances", [
            'stage_id' => $this->stage(8)->id,
            'comment' => 'Piezas cortadas, empiezo el armado.',
            'photos' => [UploadedFile::fake()->image('corte.jpg')],
            'visible_to_client' => '1', // un carpintero no puede publicar
        ])->assertRedirect()->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame('Armado', $order->stage->name);
        $update = $order->updates()->sole();
        $this->assertSame($this->stage(7)->id, $update->from_stage_id);
        $this->assertFalse($update->visible_to_client);
        $photo = $update->photos()->sole();
        $this->assertFalse($photo->visible_to_client);
        Storage::disk('local')->assertExists($photo->path);
    }

    public function test_carpenter_cannot_skip_stages_but_admin_can(): void
    {
        $juan = User::factory()->carpintero()->create();
        $admin = User::factory()->admin()->create();
        $order = WorkOrder::factory()->create(['stage_id' => $this->stage(7)->id]);
        $order->carpenters()->attach($juan);

        $this->actingAs($juan)->post("/trabajos/{$order->id}/avances", ['stage_id' => $this->stage(10)->id])
            ->assertSessionHasErrors('stage_id');
        $this->assertSame($this->stage(7)->id, $order->fresh()->stage_id);

        $this->actingAs($admin)->post("/trabajos/{$order->id}/avances", ['stage_id' => $this->stage(11)->id])
            ->assertSessionHasNoErrors();
        $this->assertNotNull($order->fresh()->delivered_at);
    }

    public function test_empty_update_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $order = WorkOrder::factory()->create();

        $this->actingAs($admin)->post("/trabajos/{$order->id}/avances", [])->assertSessionHasErrors('comment');
        $this->assertSame(0, $order->updates()->count());
    }

    public function test_client_only_sees_published_photos_of_own_orders(): void
    {
        $admin = User::factory()->admin()->create();
        $clientUser = User::factory()->cliente()->create();
        $order = WorkOrder::factory()->create(['client_id' => Client::factory()->create(['user_id' => $clientUser->id])->id]);
        $foreign = WorkOrder::factory()->create();

        $this->actingAs($admin)->post("/trabajos/{$order->id}/avances", ['photos' => [UploadedFile::fake()->image('a.jpg')]]);
        $this->actingAs($admin)->post("/trabajos/{$foreign->id}/avances", ['photos' => [UploadedFile::fake()->image('b.jpg')], 'visible_to_client' => '1']);
        $hidden = $order->updates()->sole()->photos()->sole();
        $foreignPhoto = $foreign->updates()->sole()->photos()->sole();

        $this->actingAs($clientUser)->get("/fotos/{$hidden->id}")->assertNotFound();
        $this->actingAs($clientUser)->get("/fotos/{$foreignPhoto->id}")->assertForbidden();

        $this->actingAs($admin)->patch("/admin/fotos/{$hidden->id}/visibilidad")->assertRedirect();
        $this->actingAs($clientUser)->get("/fotos/{$hidden->id}")->assertOk();
    }
}
