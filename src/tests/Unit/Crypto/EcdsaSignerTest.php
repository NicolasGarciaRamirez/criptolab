<?php

namespace Tests\Unit\Crypto;

use App\Services\Crypto\Registries\SignerRegistry;
use Tests\TestCase;

class EcdsaSignerTest extends TestCase
{
    public function test_ecdsa_p256_and_p384_sign_and_verify(): void
    {
        foreach (['ecdsa-p256', 'ecdsa-p384'] as $id) {
            $signer = app(SignerRegistry::class)->get($id);
            $pair = $signer->generateKeyPair();
            $signature = $signer->sign('mensaje ECDSA', $pair->privateKey);

            $this->assertTrue($signer->verify('mensaje ECDSA', $signature, $pair->publicKey), $id);
            $this->assertFalse($signer->verify('mensaje alterado', $signature, $pair->publicKey), $id);
        }
    }

    public function test_registry_contains_ecdsa_algorithms(): void
    {
        $ids = array_keys(app(SignerRegistry::class)->all());

        $this->assertContains('ecdsa-p256', $ids);
        $this->assertContains('ecdsa-p384', $ids);
    }
}
