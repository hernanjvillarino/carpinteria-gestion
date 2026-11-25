<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Client;
use App\Models\User;
use Database\Seeders\StageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_client_with_portal_access(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/admin/clientes', [
            'name' => 'Laura Fernández',
            'email' => 'laura@example.com',
            'portal_access' => '1',
        ])->assertRedirect()->assertSessionHas('status', fn ($s) => str_contains($s, 'Contraseña inicial'));

        $client = Client::sole();
        $this->assertSame(Role::Cliente, $client->user->role);
        $this->assertSame('laura@example.com', $client->user->email);
    }

    public function test_admin_creates_carpenter(): void
    {
        $this->actingAs(User::factory()->admin()->create())->post('/admin/usuarios', [
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'role' => 'carpintero',
            'password' => 'secreta123',
            'is_active' => '1',
        ])->assertRedirect('/admin/usuarios');

        $this->assertSame(Role::Carpintero, User::where('email', 'juan@example.com')->sole()->role);
    }

    public function test_admin_cannot_demote_or_disable_self(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put("/admin/usuarios/{$admin->id}", [
            'name' => $admin->name, 'email' => $admin->email, 'role' => 'carpintero', 'is_active' => '1',
        ])->assertSessionHasErrors('role');

        $this->assertSame(Role::Admin, $admin->fresh()->role);
    }

    public function test_admin_pages_render(): void
    {
        $this->seed(StageSeeder::class);
        $this->actingAs(User::factory()->admin()->create());

        foreach (['/admin', '/admin/trabajos', '/admin/trabajos/create', '/admin/clientes', '/admin/clientes/create', '/admin/barrios', '/admin/barrios/create', '/admin/usuarios', '/admin/usuarios/create'] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
