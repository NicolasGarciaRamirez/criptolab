<?php

namespace Tests\Unit\Crypto;

use App\Exceptions\CryptoException;
use App\Services\Crypto\Asymmetric\RsaOaep;
use App\Services\Crypto\KeyInspectionService;
use App\Services\Crypto\Registries\SignerRegistry;
use Tests\TestCase;

class KeyInspectionTest extends TestCase
{
    public function test_inspects_rsa_public_and_private_keys_with_same_fingerprint(): void
    {
        $pair = (new RsaOaep(2048))->generateKeyPair();
        $service = app(KeyInspectionService::class);

        $public = $service->inspect($pair->publicKey);
        $private = $service->inspect($pair->privateKey);

        $this->assertSame('public', $public['kind']);
        $this->assertSame('private', $private['kind']);
        $this->assertSame('RSA', $private['type']);
        $this->assertSame(2048, $private['bits']);
        $this->assertSame($public['fingerprint'], $private['fingerprint']);
    }

    public function test_rejects_incompatible_ec_key_for_rsa_context(): void
    {
        $ecPair = app(SignerRegistry::class)->get('ecdsa-p256')->generateKeyPair();
        $inspection = app(KeyInspectionService::class)->inspect($ecPair->publicKey);

        $this->expectException(CryptoException::class);
        app(KeyInspectionService::class)->assertCompatible('rsa-oaep-2048', $inspection);
    }
}
