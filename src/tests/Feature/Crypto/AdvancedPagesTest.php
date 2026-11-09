<?php

namespace Tests\Feature\Crypto;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvancedPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_advanced_labs(): void
    {
        foreach (['/lab/simetrico/archivos', '/lab/asimetrico/x25519', '/lab/hash/rainbow', '/lab/retos'] as $path) {
            $this->get($path)->assertRedirect('/login');
        }
    }

    public function test_all_advanced_labs_render_with_guides(): void
    {
        $user = User::factory()->create();

        $paths = [
            '/lab',
            '/lab/simetrico',
            '/lab/asimetrico',
            '/lab/hash',
            '/lab/autenticacion-cifrada',
            '/lab/simetrico/archivos',
            '/lab/simetrico/modos',
            '/lab/simetrico/aad',
            '/lab/asimetrico/x25519',
            '/lab/asimetrico/firma-cifrado',
            '/lab/asimetrico/certificados',
            '/lab/asimetrico/importar',
            '/lab/hash/tiempo-seguro',
            '/lab/hash/rainbow',
            '/lab/benchmark',
            '/lab/comparador',
            '/lab/retos',
            '/lab/llaves',
            '/lab/historial',
        ];

        foreach ($paths as $path) {
            $this->actingAs($user)->get($path)
                ->assertOk()
                ->assertSee('Instructivo');
        }
    }
}
