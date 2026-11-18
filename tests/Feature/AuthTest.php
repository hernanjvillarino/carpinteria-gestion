<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_role_lands_on_its_own_home(): void
    {
        foreach (['admin' => '/admin', 'carpintero' => '/taller', 'cliente' => '/mi-cuenta'] as $role => $home) {
            $user = User::factory()->{$role}()->create();
            $this->post('/ingresar', ['email' => $user->email, 'password' => 'password'])->assertRedirect($home);
            $this->post('/salir');
        }
    }

    public function test_inactive_users_cannot_log_in(): void
    {
        $user = User::factory()->carpintero()->inactive()->create();

        $this->post('/ingresar', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/ingresar');
    }

    public function test_roles_cannot_enter_other_panels(): void
    {
        $this->actingAs(User::factory()->carpintero()->create())->get('/admin')->assertForbidden();
        $this->actingAs(User::factory()->cliente()->create())->get('/taller')->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get('/taller')->assertForbidden();
    }
}
