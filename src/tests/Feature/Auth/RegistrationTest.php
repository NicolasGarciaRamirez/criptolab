<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\Crypto\BlindIndexService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->get('/register')->assertOk();
    }

    public function test_new_users_can_register_with_encrypted_email_and_argon2id_password(): void
    {
        $response = $this->post('/register', [
            'name' => 'Ana Estudiante',
            'username' => 'ana.estudiante',
            'email' => 'ana@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('username', 'ana.estudiante')->firstOrFail();
        $this->assertSame('ana@example.com', $user->email);

        $raw = DB::table('users')->where('id', $user->id)->first();
        $this->assertNotSame('ana@example.com', $raw->email);
        $this->assertSame(
            app(BlindIndexService::class)->hash('ana@example.com'),
            $raw->email_hash,
        );
        $this->assertStringStartsWith('$argon2id$', $raw->password);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'registered',
        ]);
    }

    public function test_email_is_unique_even_with_different_case(): void
    {
        User::factory()->create(['email' => 'ana@example.com']);

        $this->post('/register', [
            'name' => 'Otra Ana',
            'username' => 'otra.ana',
            'email' => 'ANA@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_username_must_be_unique(): void
    {
        User::factory()->create(['username' => 'ana']);

        $this->post('/register', [
            'name' => 'Otra Ana',
            'username' => 'ANA',
            'email' => 'otra@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertSessionHasErrors('username');
    }
}
