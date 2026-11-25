<?php

namespace Tests\Feature;

use App\Enums\DesignStatus;
use App\Models\Client;
use App\Models\Design;
use App\Models\Stage;
use App\Models\User;
use App\Models\WorkOrder;
use App\Notifications\DesignAnswered;
use App\Notifications\DesignSentToClient;
use App\Notifications\WorkOrderStageChanged;
use Database\Seeders\StageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClientPortalTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $clientUser;

    private WorkOrder $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(StageSeeder::class);
        Storage::fake('local');
        Notification::fake();

        $this->admin = User::factory()->admin()->create();
        $this->clientUser = User::factory()->cliente()->create();
        $this->order = WorkOrder::factory()->create([
            'title' => 'Cocina de Laura',
            'client_id' => Client::factory()->create(['user_id' => $this->clientUser->id])->id,
        ]);
    }

    private function stage(int $position): Stage
    {
        return Stage::where('position', $position)->firstOrFail();
    }

    public function test_client_sees_own_orders_only(): void
    {
        $foreign = WorkOrder::factory()->create(['title' => 'Placard ajeno']);

        $this->actingAs($this->clientUser)->get('/mi-cuenta')->assertOk()->assertSee('Cocina de Laura')->assertDontSee('Placard ajeno');
        $this->actingAs($this->clientUser)->get("/mi-cuenta/pedidos/{$this->order->id}")->assertOk();
        $this->actingAs($this->clientUser)->get("/mi-cuenta/pedidos/{$foreign->id}")->assertForbidden();
    }

    public function test_timeline_shows_stage_changes_and_only_published_comments(): void
    {
        $this->order->recordUpdate($this->admin, $this->stage(2), 'Comentario interno del taller', false);
        $this->order->recordUpdate($this->admin, null, 'Ya tomamos las medidas', true);

        $this->actingAs($this->clientUser)->get("/mi-cuenta/pedidos/{$this->order->id}")
            ->assertSee('Pasó a la etapa «Medición»')
            ->assertSee('Ya tomamos las medidas')
            ->assertDontSee('Comentario interno del taller');
    }

    public function test_client_is_emailed_on_visible_stage_changes_only(): void
    {
        $this->order->recordUpdate($this->admin, $this->stage(2), null, false); // Medición: visible
        $this->order->recordUpdate($this->admin, $this->stage(6), null, false); // Despiece: interna
        $this->order->recordUpdate($this->admin, null, 'Solo comentario', true);

        Notification::assertSentToTimes($this->clientUser, WorkOrderStageChanged::class, 1);
        Notification::assertSentTo($this->clientUser, WorkOrderStageChanged::class, fn ($n) => $n->stage->name === 'Medición');
    }

    public function test_admin_uploads_design_and_client_approves_it(): void
    {
        $this->actingAs($this->admin)->post("/admin/trabajos/{$this->order->id}/disenos", [
            'preview' => UploadedFile::fake()->image('cocina.png'),
            'skp' => UploadedFile::fake()->create('cocina.skp', 500),
            'notes' => 'Melamina blanca con frentes Roble Dakar',
            'send' => '1',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $design = Design::sole();
        $this->assertSame(1, $design->version);
        $this->assertSame(DesignStatus::Enviado, $design->status);
        Notification::assertSentTo($this->clientUser, DesignSentToClient::class);

        $this->actingAs($this->clientUser)->get("/mi-cuenta/pedidos/{$this->order->id}")->assertSee('Diseño para aprobar');
        $this->actingAs($this->clientUser)->get("/disenos/{$design->id}/imagen")->assertOk();
        $this->actingAs($this->clientUser)->get("/disenos/{$design->id}/sketchup")->assertNotFound();

        $this->actingAs($this->clientUser)->post("/mi-cuenta/disenos/{$design->id}/respuesta", ['decision' => 'aprobar'])
            ->assertSessionHasNoErrors();

        $design->refresh();
        $this->assertSame(DesignStatus::Aprobado, $design->status);
        $this->assertSame($this->clientUser->id, $design->approved_by);
        $this->assertTrue($this->order->approvedDesign()->is($design));
        Notification::assertSentTo($this->admin, DesignAnswered::class);

        // No se puede responder dos veces.
        $this->actingAs($this->clientUser)->post("/mi-cuenta/disenos/{$design->id}/respuesta", ['decision' => 'cambios', 'feedback' => 'x'])
            ->assertSessionHasErrors('design');
    }

    public function test_client_requests_changes_with_feedback(): void
    {
        $design = $this->order->designs()->create(['version' => 1, 'preview_path' => 'x.png', 'status' => DesignStatus::Enviado, 'sent_at' => now()]);

        $this->actingAs($this->clientUser)->post("/mi-cuenta/disenos/{$design->id}/respuesta", ['decision' => 'cambios'])
            ->assertSessionHasErrors('feedback');

        $this->actingAs($this->clientUser)->post("/mi-cuenta/disenos/{$design->id}/respuesta", ['decision' => 'cambios', 'feedback' => 'Alacena 10 cm más alta'])
            ->assertSessionHasNoErrors();

        $this->assertSame(DesignStatus::Rechazado, $design->fresh()->status);
        $this->assertSame('Alacena 10 cm más alta', $design->fresh()->client_feedback);
    }

    public function test_drafts_are_hidden_from_client_and_versions_increment(): void
    {
        foreach (['a.png', 'b.png'] as $file) {
            $this->actingAs($this->admin)->post("/admin/trabajos/{$this->order->id}/disenos", ['preview' => UploadedFile::fake()->image($file)]);
        }
        [$v2, $v1] = $this->order->designs()->get()->all();

        $this->assertSame([2, 1], [$v2->version, $v1->version]);
        $this->assertSame(DesignStatus::Borrador, $v1->status);
        Notification::assertNothingSent();
        $this->actingAs($this->clientUser)->get("/disenos/{$v1->id}/imagen")->assertNotFound();
        $this->actingAs($this->clientUser)->post("/mi-cuenta/disenos/{$v1->id}/respuesta", ['decision' => 'aprobar'])->assertSessionHasErrors('design');
    }

    public function test_carpenter_downloads_skp_of_assigned_order(): void
    {
        $juan = User::factory()->carpintero()->create();
        $this->order->carpenters()->attach($juan);
        Storage::disk('local')->put('d/v1.skp', 'skp');
        $design = $this->order->designs()->create(['version' => 1, 'preview_path' => 'd/p.png', 'skp_path' => 'd/v1.skp', 'status' => DesignStatus::Aprobado]);

        $this->actingAs($juan)->get("/disenos/{$design->id}/sketchup")->assertOk()->assertDownload("{$this->order->code}-diseno-v1.skp");
    }
}
