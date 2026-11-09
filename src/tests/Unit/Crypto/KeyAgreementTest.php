<?php

namespace Tests\Unit\Crypto;

use App\Services\Crypto\KeyAgreementService;
use Tests\TestCase;

class KeyAgreementTest extends TestCase
{
    public function test_both_parties_derive_the_same_secret_and_key(): void
    {
        $service = app(KeyAgreementService::class);
        $alice = $service->generatePair();
        $bob = $service->generatePair();

        $aliceShared = $service->sharedSecret($alice['private_key'], $bob['public_key']);
        $bobShared = $service->sharedSecret($bob['private_key'], $alice['public_key']);

        $this->assertSame($aliceShared, $bobShared);
        $this->assertSame($service->fingerprint($aliceShared), $service->fingerprint($bobShared));

        $aliceKey = $service->deriveAesKey($aliceShared);
        $bobKey = $service->deriveAesKey($bobShared);
        $this->assertSame($aliceKey['key'], $bobKey['key']);

        $envelope = $service->encryptMessage('mensaje compartido', $aliceShared);
        $this->assertSame('mensaje compartido', $service->decryptMessage($envelope, $bobShared));
    }
}
