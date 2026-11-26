<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Models\Client;
use App\Models\DocumentType;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\GatedCommunity;
use App\Models\User;
use App\Models\WorkOrder;
use App\Notifications\DocumentsExpiring;
use Database\Seeders\StageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StaffDocumentsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private DocumentType $art;

    private DocumentType $dni;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['carpinteria.document_warning_days' => 15]);
        $this->admin = User::factory()->admin()->create();
        // La migración carga la lista inicial.
        $this->art = DocumentType::where('name', 'ART')->firstOrFail();
        $this->dni = DocumentType::where('name', 'DNI')->firstOrFail();
    }

    private function document(Employee $employee, DocumentType $type, ?string $expires): EmployeeDocument
    {
        return $employee->documents()->create([
            'document_type_id' => $type->id, 'path' => "personal/{$employee->id}/x.pdf", 'original_name' => 'x.pdf',
            'expires_on' => $expires,
        ]);
    }

    public function test_barrio_requirements_are_saved_and_shown(): void
    {
        $this->actingAs($this->admin)->post('/admin/barrios', [
            'name' => 'Los Robles', 'city' => 'Pilar', 'requisitos' => [$this->art->id, $this->dni->id],
        ])->assertRedirect();

        $barrio = GatedCommunity::where('name', 'Los Robles')->firstOrFail();
        $this->assertEqualsCanonicalizing([$this->art->id, $this->dni->id], $barrio->requiredDocumentTypes->pluck('id')->all());

        $this->get("/admin/barrios/{$barrio->id}")->assertOk()->assertSee('ART')->assertSee('DNI');
    }

    public function test_barrio_specific_document_is_always_required(): void
    {
        $barrio = GatedCommunity::factory()->create();
        $barrio->requiredDocumentTypes()->sync([$this->art->id]);
        $credencial = DocumentType::create(['name' => 'Credencial', 'gated_community_id' => $barrio->id]);

        $this->assertEqualsCanonicalizing(['ART', 'Credencial'], $barrio->fresh()->requiredTypes()->pluck('name')->all());

        $employee = Employee::factory()->create();
        $this->document($employee, $this->art, today()->addYear()->toDateString());
        $this->assertFalse($employee->fresh()->isEnabledFor($barrio->fresh()));

        $this->document($employee, $credencial, today()->addMonth()->toDateString());
        $this->assertTrue($employee->fresh()->isEnabledFor($barrio->fresh()));
    }

    public function test_status_follows_expiry_and_newest_document_wins(): void
    {
        $employee = Employee::factory()->create();
        $this->assertSame(DocumentStatus::Falta, $employee->statusFor($this->art));

        $this->document($employee, $this->art, today()->subDay()->toDateString());
        $this->assertSame(DocumentStatus::Vencido, $employee->fresh()->statusFor($this->art));

        $this->document($employee, $this->art, today()->addDays(10)->toDateString());
        $this->assertSame(DocumentStatus::PorVencer, $employee->fresh()->statusFor($this->art));
        $this->assertTrue(DocumentStatus::PorVencer->allowsEntry());

        $this->document($employee, $this->art, today()->addDays(60)->toDateString());
        $this->assertSame(DocumentStatus::Vigente, $employee->fresh()->statusFor($this->art));

        // El que vence hoy todavía sirve.
        $other = Employee::factory()->create();
        $this->document($other, $this->art, today()->toDateString());
        $this->assertTrue($other->fresh()->statusFor($this->art)->allowsEntry());

        $this->document($other, $this->dni, null);
        $this->assertSame(DocumentStatus::Vigente, $other->fresh()->statusFor($this->dni));
    }

    public function test_admin_uploads_document_privately_and_downloads_it(): void
    {
        $employee = Employee::factory()->create();

        $this->actingAs($this->admin)->post("/admin/personal/{$employee->id}/documentos", [
            'document_type_id' => $this->art->id,
            'file' => UploadedFile::fake()->create('art.pdf', 200, 'application/pdf'),
        ])->assertSessionHasErrors('expires_on'); // la ART vence: la fecha es obligatoria

        $this->post("/admin/personal/{$employee->id}/documentos", [
            'document_type_id' => $this->art->id,
            'file' => UploadedFile::fake()->create('art.pdf', 200, 'application/pdf'),
            'expires_on' => today()->addMonths(3)->toDateString(),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $doc = $employee->documents()->firstOrFail();
        Storage::disk('local')->assertExists($doc->path);
        $this->get("/admin/documentos/{$doc->id}")->assertOk();
        $this->get("/admin/personal/{$employee->id}")->assertOk()->assertSee('Vigente');

        // DNI no vence: se carga sin fecha.
        $this->post("/admin/personal/{$employee->id}/documentos", [
            'document_type_id' => $this->dni->id,
            'file' => UploadedFile::fake()->image('dni.jpg'),
        ])->assertSessionHasNoErrors();

        $this->delete("/admin/documentos/{$doc->id}")->assertRedirect();
        Storage::disk('local')->assertMissing($doc->path);
    }

    public function test_only_admins_reach_staff_documents(): void
    {
        $employee = Employee::factory()->create();
        $doc = $this->document($employee, $this->art, today()->addYear()->toDateString());
        $carpintero = User::factory()->carpintero()->create();

        $this->actingAs($carpintero)->get('/admin/personal')->assertForbidden();
        $this->actingAs($carpintero)->get("/admin/documentos/{$doc->id}")->assertForbidden();
    }

    public function test_creating_a_carpenter_creates_staff_record(): void
    {
        $this->actingAs($this->admin)->post('/admin/usuarios', [
            'name' => 'Pedro Sosa', 'email' => 'pedro@example.com', 'role' => 'carpintero',
            'password' => 'secreto-largo-123', 'password_confirmation' => 'secreto-largo-123', 'is_active' => '1',
        ])->assertRedirect();

        $this->assertSame('Pedro Sosa', User::where('email', 'pedro@example.com')->first()->employee?->name);
    }

    public function test_staff_pages_render(): void
    {
        $barrio = GatedCommunity::factory()->create();
        $barrio->requiredDocumentTypes()->sync([$this->art->id]);
        $employee = Employee::factory()->create(['name' => 'Ana Ríos']);
        $this->document($employee, $this->art, today()->addDays(5)->toDateString());

        $this->actingAs($this->admin);
        $this->get('/admin/personal')->assertOk()->assertSee('Vencimientos para atender')->assertSee('Ana Ríos');
        $this->get('/admin/personal/create')->assertOk();
        $this->get("/admin/personal/{$employee->id}/edit")->assertOk();
        $this->get('/admin/documentacion')->assertOk()->assertSee('ART');
        $this->get('/admin/barrios')->assertOk()->assertSee('1 de 1');
        $this->get("/admin/barrios/{$barrio->id}")->assertOk()->assertSee('Ana Ríos')->assertSee('Nómina para la administración');
    }

    public function test_work_order_warns_when_assigned_carpenter_cannot_enter(): void
    {
        $this->seed(StageSeeder::class);
        $barrio = GatedCommunity::factory()->create(['name' => 'Santa Clara']);
        $barrio->requiredDocumentTypes()->sync([$this->art->id]);
        $juan = User::factory()->carpintero()->create(['name' => 'Juan']);
        $juanEmployee = Employee::factory()->create(['user_id' => $juan->id, 'name' => 'Juan']);
        $this->document($juanEmployee, $this->art, today()->subDays(3)->toDateString());

        $order = WorkOrder::factory()->create(['client_id' => Client::factory()->create(['gated_community_id' => $barrio->id])->id]);
        $order->carpenters()->attach($juan->id, ['is_lead' => true]);

        $this->actingAs($this->admin)->get("/admin/trabajos/{$order->id}")
            ->assertOk()->assertSee('no todos los asignados pueden entrar a Santa Clara')->assertSee('falta o venció: ART');

        $this->document($juanEmployee, $this->art, today()->addYear()->toDateString());
        $this->get("/admin/trabajos/{$order->id}")->assertDontSee('no todos los asignados pueden entrar');
    }

    public function test_expiry_notice_is_sent_once_per_stage(): void
    {
        Notification::fake();
        $employee = Employee::factory()->create();
        $doc = $this->document($employee, $this->art, today()->addDays(10)->toDateString());
        $this->document(Employee::factory()->create(), $this->art, today()->addDays(90)->toDateString()); // lejos: no avisa
        $this->document(Employee::factory()->create(['is_active' => false]), $this->art, today()->addDay()->toDateString()); // inactivo: no avisa

        $this->artisan('personal:avisar-vencimientos')->assertSuccessful();
        Notification::assertSentTo($this->admin, DocumentsExpiring::class, fn ($n) => $n->documents->pluck('id')->all() === [$doc->id]);
        $this->assertNotNull($doc->fresh()->expiring_notified_at);

        // Al otro día no repite.
        Notification::fake();
        $this->artisan('personal:avisar-vencimientos')->assertSuccessful();
        Notification::assertNothingSent();

        // Cuando vence, avisa de nuevo una sola vez.
        $this->travel(11)->days();
        $this->artisan('personal:avisar-vencimientos')->assertSuccessful();
        Notification::assertSentTo($this->admin, DocumentsExpiring::class);
        $this->assertNotNull($doc->fresh()->expired_notified_at);
    }

    public function test_renewed_document_stops_notices(): void
    {
        Notification::fake();
        $employee = Employee::factory()->create();
        $this->document($employee, $this->art, today()->subDay()->toDateString());
        $this->document($employee, $this->art, today()->addYear()->toDateString()); // ya renovado

        $this->artisan('personal:avisar-vencimientos')->assertSuccessful();
        Notification::assertNothingSent();
    }
}
