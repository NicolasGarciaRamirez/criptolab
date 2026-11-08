<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_users_can_authenticate_using_username(): void
    {
        $user = User::factory()->create(['username' => 'ana.demo']);

        $response = $this->post('/login', [
            'username' => 'ana.demo',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'login',
        ]);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        User::factory()->create(['username' => 'ana.demo']);

        $this->post('/login', [
            'username' => 'ana.demo',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', ['action' => 'login_failed']);
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'logout',
        ]);
    }

    public function test_login_is_rate_limited_after_five_attempts(): void
    {
        $user = User::factory()->create(['username' => 'ana.demo']);

        foreach (range(1, 5) as $attempt) {
            $this->post('/login', [
                'username' => $user->username,
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('username');
        }

        $this->post('/login', [
            'username' => $user->username,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('username');

        $this->assertDatabaseCount('audit_logs', 5);
    }
}
