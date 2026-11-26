<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Stage;
use App\Models\User;
use App\Models\WorkOrder;
use App\Notifications\RatingRequested;
use App\Notifications\WorkOrderRated;
use Database\Seeders\StageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RatingTest extends TestCase
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
        $this->order = WorkOrder::factory()->create(['client_id' => Client::factory()->create(['user_id' => $this->clientUser->id])->id]);
    }

    private function scores(int $value = 5): array
    {
        return ['score_work' => $value, 'score_materials' => $value, 'score_installation' => 4, 'score_staff' => $value, 'score_service' => $value];
    }

    private function deliver(): void
    {
        $this->order->recordUpdate($this->admin, Stage::query()->orderByDesc('position')->first(), null, false);
    }

    public function test_client_is_asked_to_rate_when_the_order_is_delivered(): void
    {
        $this->deliver();

        Notification::assertSentTo($this->clientUser, RatingRequested::class);
        $this->actingAs($this->clientUser)->get('/mi-cuenta')->assertSee('Calificá el trabajo');
        $this->get("/mi-cuenta/pedidos/{$this->order->id}")->assertSee('Enviar calificación');
    }

    public function test_cannot_rate_before_delivery(): void
    {
        $this->actingAs($this->clientUser)->post("/mi-cuenta/pedidos/{$this->order->id}/calificacion", $this->scores())
            ->assertSessionHasErrors('rating');
        $this->assertNull($this->order->rating);
    }

    public function test_client_rates_once_with_photos_and_owner_sees_it(): void
    {
        $this->deliver();

        $this->actingAs($this->clientUser)->post("/mi-cuenta/pedidos/{$this->order->id}/calificacion", [
            ...$this->scores(5),
            'comment' => 'Excelente terminación',
            'photos' => [UploadedFile::fake()->image('cocina.jpg')],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $rating = $this->order->fresh()->rating;
        $this->assertSame(4.8, $rating->average());
        $this->assertCount(1, $rating->photos);
        Storage::disk('local')->assertExists($rating->photos[0]->path);
        Notification::assertSentTo($this->admin, WorkOrderRated::class);

        // Una sola vez.
        $this->post("/mi-cuenta/pedidos/{$this->order->id}/calificacion", $this->scores(1))->assertSessionHasErrors('rating');
        $this->get("/mi-cuenta/pedidos/{$this->order->id}")->assertSee('Tu calificación')->assertSee('Excelente terminación');

        $this->actingAs($this->admin)->get("/admin/trabajos/{$this->order->id}")->assertSee('Calificación del cliente')->assertSee('4,8');
        $this->get('/admin')->assertSee('Satisfacción de clientes')->assertSee('Excelente terminación');
        $this->get(route('calificaciones.fotos', $rating->photos[0]))->assertOk();
    }

    public function test_other_clients_and_carpenters_cannot_rate_or_see_photos(): void
    {
        $this->deliver();
        $this->actingAs($this->clientUser)->post("/mi-cuenta/pedidos/{$this->order->id}/calificacion", [
            ...$this->scores(), 'photos' => [UploadedFile::fake()->image('a.jpg')],
        ]);
        $photo = $this->order->fresh()->rating->photos[0];

        $other = User::factory()->cliente()->create();
        Client::factory()->create(['user_id' => $other->id]);
        $this->actingAs($other)->post("/mi-cuenta/pedidos/{$this->order->id}/calificacion", $this->scores())->assertForbidden();
        $this->actingAs($other)->get(route('calificaciones.fotos', $photo))->assertForbidden();

        $carpintero = User::factory()->carpintero()->create();
        $this->actingAs($carpintero)->get(route('calificaciones.fotos', $photo))->assertForbidden();
    }

    public function test_scores_must_be_between_one_and_five(): void
    {
        $this->deliver();
        $this->actingAs($this->clientUser)->post("/mi-cuenta/pedidos/{$this->order->id}/calificacion", [...$this->scores(), 'score_work' => 6])
            ->assertSessionHasErrors('score_work');
    }
}
