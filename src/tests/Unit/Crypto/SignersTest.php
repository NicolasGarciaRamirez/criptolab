<?php

namespace Tests\Unit\Crypto;

use App\Services\Crypto\Registries\SignerRegistry;
use Tests\TestCase;

class SignersTest extends TestCase
{
    public function test_rsa_signatures_verify_and_reject_tampering(): void
    {
        $signer = app(SignerRegistry::class)->get('rsa-sha256-2048');
        $pair = $signer->generateKeyPair();
        $message = 'Contenido firmado';

        $signature = $signer->sign($message, $pair->privateKey);

        $this->assertTrue($signer->verify($message, $signature, $pair->publicKey));
        $this->assertFalse($signer->verify($message.' alterado', $signature, $pair->publicKey));
        $this->assertFalse($signer->verify($message, base64_encode('firma-invalida'), $pair->publicKey));
    }

    public function test_ed25519_signatures_verify_and_reject_tampering(): void
    {
        $signer = app(SignerRegistry::class)->get('ed25519');
        $pair = $signer->generateKeyPair();
        $message = 'Documento Ed25519';

        $signature = $signer->sign($message, $pair->privateKey);

        $this->assertTrue($signer->verify($message, $signature, $pair->publicKey));
        $this->assertFalse($signer->verify($message.' alterado', $signature, $pair->publicKey));
        $this->assertFalse($signer->verify($message, base64_encode(str_repeat('x', 64)), $pair->publicKey));
    }

    public function test_registry_contains_rsa_and_ed25519_signers(): void
    {
        $ids = array_keys(app(SignerRegistry::class)->all());

        $this->assertContains('rsa-sha256-2048', $ids);
        $this->assertContains('rsa-sha256-4096', $ids);
        $this->assertContains('ed25519', $ids);
    }
}
