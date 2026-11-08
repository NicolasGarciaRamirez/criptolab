<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

class SecurityTest extends TestCase
{
    public function test_security_headers_are_present(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    public function test_session_configuration_is_hardened(): void
    {
        $this->assertTrue((bool) config('session.encrypt'));
        $this->assertTrue((bool) config('session.http_only'));
        $this->assertSame('lax', config('session.same_site'));
        $this->assertFalse((bool) config('session.secure'));
    }

    public function test_custom_404_page_is_rendered(): void
    {
        $this->get('/ruta-que-no-existe')
            ->assertNotFound()
            ->assertSee('Página no encontrada')
            ->assertSee('404');
    }

    public function test_custom_419_and_500_views_exist(): void
    {
        $this->view('errors.419')->assertSee('Sesión expirada');
        $this->view('errors.500')->assertSee('Error interno');
    }

    public function test_app_timezone_and_locale_are_configured(): void
    {
        $this->assertSame('America/Bogota', config('app.timezone'));
        $this->assertSame('es', config('app.locale'));
    }
}
