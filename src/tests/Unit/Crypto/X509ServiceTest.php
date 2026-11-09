<?php

namespace Tests\Unit\Crypto;

use App\Exceptions\CryptoException;
use App\Services\Crypto\X509Service;
use Tests\TestCase;

class X509ServiceTest extends TestCase
{
    public function test_self_signed_certificate_can_be_generated_and_inspected(): void
    {
        $service = app(X509Service::class);
        $result = $service->generateSelfSigned('criptolab.test', 'CriptoLab', 'CO', 30);

        $this->assertStringContainsString('BEGIN CERTIFICATE', $result['certificate']);
        $this->assertStringContainsString('BEGIN PRIVATE KEY', $result['private_key']);
        $this->assertStringContainsString('BEGIN CERTIFICATE REQUEST', $result['csr']);
        $this->assertTrue($result['verified']);
        $this->assertSame('criptolab.test', $result['parsed']['subject']['CN']);

        $inspected = $service->inspect($result['certificate']);
        $this->assertTrue($inspected['verified']);
        $this->assertSame($result['fingerprint'], $inspected['fingerprint']);
    }

    public function test_invalid_certificate_is_rejected(): void
    {
        $this->expectException(CryptoException::class);
        app(X509Service::class)->inspect('no es un certificado');
    }
}
