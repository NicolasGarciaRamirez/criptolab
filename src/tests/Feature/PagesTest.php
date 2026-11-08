<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_is_public_and_explains_the_activity(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Criptografía aplicada en PHP y Laravel')
            ->assertSee('tabla de equivalencias Java')
            ->assertSee('Formulario de autenticación cifrado');
    }

    public function test_theory_page_includes_java_php_equivalences(): void
    {
        $this->get('/teoria')
            ->assertOk()
            ->assertSee('Simétrico, asimétrico y hash')
            ->assertSee('Equivalencias Java ↔ PHP')
            ->assertSee('hash_pbkdf2');
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_authenticated_dashboard_shows_summary(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee('Panel de')
            ->assertSee('Operaciones')
            ->assertSee('Pares de llaves');
    }
}
