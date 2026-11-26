<?php

namespace Tests\Feature;

use App\Enums\QuoteStatus;
use App\Models\Client;
use App\Models\InstructionTemplate;
use App\Models\Stage;
use App\Models\User;
use App\Models\WorkOrder;
use App\Notifications\WorkOrderAssigned;
use App\Notifications\WorkOrderProgressed;
use Database\Seeders\InstructionTemplateSeeder;
use Database\Seeders\StageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WorkshopOwnerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $juan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([StageSeeder::class, InstructionTemplateSeeder::class]);
        $this->admin = User::factory()->admin()->create();
        $this->juan = User::factory()->carpintero()->create(['name' => 'Juan']);
    }

    public function test_lead_time_defaults_to_config_and_recalculates_delivery(): void
    {
        config(['carpinteria.default_lead_time_days' => 30]);
        $order = WorkOrder::factory()->create(['lead_time_days' => null, 'started_on' => '2026-11-01']);

        $this->assertSame(30, $order->lead_time_days);
        $this->assertSame('2026-12-01', $order->estimated_delivery->toDateString());

        $order->update(['lead_time_days' => 60]);
        $this->assertSame('2026-12-31', $order->fresh()->estimated_delivery->toDateString());
    }

    public function test_assigning_a_carpenter_notifies_only_new_ones_with_tasks(): void
    {
        Notification::fake();
        $martin = User::factory()->carpintero()->create();
        $templates = InstructionTemplate::query()->active()->take(2)->pluck('id')->all();

        $this->actingAs($this->admin)->post('/admin/trabajos', [
            'client_id' => Client::factory()->create()->id,
            'title' => 'Cocina',
            'priority' => 'normal',
            'started_on' => today()->toDateString(),
            'lead_time_days' => 30,
            'carpinteros' => [$this->juan->id],
            'instrucciones' => $templates,
            'instrucciones_extra' => "Llevar escalera\n\n",
        ])->assertRedirect()->assertSessionHasNoErrors();

        $order = WorkOrder::sole();
        $this->assertSame(3, $order->tasks()->count());
        Notification::assertSentTo($this->juan, WorkOrderAssigned::class, function ($n, $channels) use ($order) {
            $data = $n->toArray($this->juan);

            return $n->order->is($order) && in_array('database', $channels) && in_array('mail', $channels)
                && str_contains($data['title'], $order->code) && str_contains($data['body'], 'Cocina');
        });

        // Al editar y sumar otro carpintero, solo se avisa al nuevo; las indicaciones ya cargadas no se duplican.
        $this->put("/admin/trabajos/{$order->id}", [
            'client_id' => $order->client_id, 'title' => 'Cocina', 'priority' => 'normal',
            'started_on' => today()->toDateString(), 'lead_time_days' => 45,
            'carpinteros' => [$this->juan->id, $martin->id], 'instrucciones' => $templates,
        ])->assertRedirect()->assertSessionHasNoErrors();

        Notification::assertSentToTimes($this->juan, WorkOrderAssigned::class, 1);
        Notification::assertSentTo($martin, WorkOrderAssigned::class);
        $this->assertSame(3, $order->tasks()->count());
    }

    public function test_owner_is_notified_when_a_carpenter_progresses(): void
    {
        Notification::fake();
        $order = WorkOrder::factory()->create();
        $order->carpenters()->attach($this->juan);
        $next = Stage::where('position', 2)->first();

        $this->actingAs($this->juan)->post("/trabajos/{$order->id}/avances", ['stage_id' => $next->id, 'comment' => 'Listo'])
            ->assertRedirect()->assertSessionHasNoErrors();

        Notification::assertSentTo($this->admin, WorkOrderProgressed::class, function ($n) use ($order, $next) {
            $data = $n->toArray($this->admin);

            return str_contains($data['title'], $order->code) && str_contains($data['title'], "pasó a {$next->name}")
                && $data['url'] === route('admin.trabajos.show', $order);
        });

        // Los avances que carga el propio dueño no le generan aviso.
        $this->actingAs($this->admin)->post("/trabajos/{$order->id}/avances", ['comment' => 'Visto'])->assertRedirect();
        Notification::assertSentToTimes($this->admin, WorkOrderProgressed::class, 1);
    }

    public function test_bell_lists_and_opens_notifications(): void
    {
        $order = WorkOrder::factory()->create();
        $order->carpenters()->attach($this->juan);
        $order->recordUpdate($this->juan, null, 'Subí las medidas', false);

        $notification = $this->admin->notifications()->sole();
        $this->actingAs($this->admin)->getJson('/avisos/sin-leer')->assertExactJson(['unread' => 1]);
        $this->get('/avisos')->assertOk()->assertSee($order->code)->assertSee('Subí las medidas');
        $this->get("/avisos/{$notification->id}")->assertRedirect(route('admin.trabajos.show', $order));
        $this->assertNotNull($notification->fresh()->read_at);

        $this->actingAs($this->juan)->get("/avisos/{$notification->id}")->assertNotFound();
    }

    public function test_assigned_carpenter_checks_tasks_but_others_cannot(): void
    {
        $order = WorkOrder::factory()->create();
        $order->carpenters()->attach($this->juan);
        $order->addTasks($this->admin, [], 'Verificar medidas');
        $task = $order->tasks()->sole();

        $this->actingAs($this->juan)->get("/taller/trabajos/{$order->id}")->assertSee('Verificar medidas');
        $this->actingAs($this->juan)->patch("/trabajos/{$order->id}/tareas/{$task->id}")->assertRedirect();
        $this->assertSame($this->juan->id, $task->fresh()->done_by);

        $otro = User::factory()->carpintero()->create();
        $this->actingAs($otro)->patch("/trabajos/{$order->id}/tareas/{$task->id}")->assertForbidden();
        $this->actingAs($this->juan)->delete("/trabajos/{$order->id}/tareas/{$task->id}")->assertForbidden();
        $this->actingAs($this->juan)->post("/trabajos/{$order->id}/tareas", ['instrucciones_extra' => 'x'])->assertForbidden();
    }

    public function test_owner_manages_instruction_templates(): void
    {
        $this->actingAs($this->admin)->post('/admin/indicaciones', ['text' => 'Usar tornillos de 4x40'])->assertRedirect();
        $template = InstructionTemplate::where('text', 'Usar tornillos de 4x40')->sole();

        $this->put("/admin/indicaciones/{$template->id}", ['text' => 'Usar tornillos 4x40', 'position' => 1])->assertRedirect();
        $this->assertFalse($template->fresh()->is_active);
        $this->get('/admin/indicaciones')->assertOk()->assertSee('Usar tornillos 4x40');

        $this->actingAs($this->juan)->get('/admin/indicaciones')->assertForbidden();
    }

    public function test_dashboard_shows_timeline_carpenters_and_live_activity(): void
    {
        $libre = User::factory()->carpintero()->create(['name' => 'Sergio']);
        $late = WorkOrder::factory()->create(['title' => 'Vestidor atrasado', 'started_on' => today()->subDays(40), 'lead_time_days' => 30]);
        $late->carpenters()->attach($this->juan);
        $late->recordUpdate($this->juan, null, 'Avance del vestidor', false);

        $this->actingAs($this->admin)->get('/admin')->assertOk()
            ->assertSee('Línea de tiempo')->assertSee($late->code)
            ->assertSee('Ocupado · 1')->assertSee('Sergio')->assertSee('Libre')
            ->assertSee('Avance del vestidor');

        $this->get('/admin/actividad')->assertOk()->assertSee('Avance del vestidor');
        $this->actingAs($this->juan)->get('/admin/actividad')->assertForbidden();
    }

    public function test_accepting_the_first_quote_starts_the_lead_time(): void
    {
        Notification::fake();
        $clientUser = User::factory()->cliente()->create();
        $order = WorkOrder::factory()->create([
            'client_id' => Client::factory()->create(['user_id' => $clientUser->id])->id,
            'started_on' => today()->subDays(20), 'lead_time_days' => 30,
        ]);
        $quote = $order->quotes()->create(['version' => 1, 'status' => QuoteStatus::Enviado, 'valid_until' => today()->addDays(10), 'created_by' => $this->admin->id]);

        $this->actingAs($clientUser)->post("/mi-cuenta/presupuestos/{$quote->id}/respuesta", ['decision' => 'aceptar'])->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertTrue($order->started_on->isToday());
        $this->assertSame(today()->addDays(30)->toDateString(), $order->estimated_delivery->toDateString());
    }

    public function test_push_subscription_is_saved_for_the_user(): void
    {
        $this->actingAs($this->juan)->postJson('/push/suscripcion', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
            'keys' => ['p256dh' => 'BPubKey', 'auth' => 'AuthToken'],
            'contentEncoding' => 'aes128gcm',
        ])->assertNoContent();

        $this->assertSame(1, $this->juan->pushSubscriptions()->count());

        $this->deleteJson('/push/suscripcion', ['endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123'])->assertNoContent();
        $this->assertSame(0, $this->juan->pushSubscriptions()->count());
    }

    public function test_pwa_files_are_served(): void
    {
        $this->assertFileExists(public_path('manifest.webmanifest'));
        $this->assertFileExists(public_path('sw.js'));
        $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true);
        foreach ($manifest['icons'] as $icon) {
            $this->assertFileExists(public_path(ltrim($icon['src'], '/')));
        }

        $this->actingAs($this->juan)->get('/taller')->assertSee('manifest.webmanifest', false);
    }
}
