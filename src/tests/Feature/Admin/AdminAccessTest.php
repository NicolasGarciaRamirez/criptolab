<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.users.index'))->assertRedirect('/login');
    }

    public function test_regular_user_gets_403_with_custom_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.users.index'))
            ->assertForbidden()
            ->assertSee('No tienes permisos');
    }

    public function test_admin_can_see_users_with_decrypted_email(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create([
            'name' => 'Persona Buscada',
            'username' => 'persona.buscada',
            'email' => 'persona@example.com',
        ]);

        $this->actingAs($admin)->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('persona.buscada')
            ->assertSee('persona@example.com')
            ->assertSee('Administrador');
    }

    public function test_admin_can_search_users_by_username(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['username' => 'ana.buscada']);
        User::factory()->create(['username' => 'otro.usuario']);

        $this->actingAs($admin)->get(route('admin.users.index', ['search' => 'ana.buscada']))
            ->assertOk()
            ->assertSee('ana.buscada')
            ->assertDontSee('otro.usuario');
    }

    public function test_admin_can_see_audit_logs(): void
    {
        $admin = User::factory()->admin()->create();

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'login',
            'description' => 'Inicio de sesión',
            'ip_address' => '127.0.0.1',
        ]);

        $this->actingAs($admin)->get(route('admin.audit.index'))
            ->assertOk()
            ->assertSee('Inicio de sesión')
            ->assertSee($admin->username);
    }
}
