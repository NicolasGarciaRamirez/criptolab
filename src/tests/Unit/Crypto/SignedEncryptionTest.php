<?php

namespace Tests\Unit\Crypto;

use App\Exceptions\CryptoException;
use App\Services\Crypto\Dto\CryptoEnvelope;
use App\Services\Crypto\SignedEncryptionService;
use Tests\TestCase;

class SignedEncryptionTest extends TestCase
{
    public function test_sign_then_encrypt_roundtrip_verifies_signature(): void
    {
        $service = app(SignedEncryptionService::class);
        $pairs = $service->generatePairs();
        $envelope = $service->signAndEncrypt(
            'mensaje firmado',
            $pairs['signing']['algorithm'],
            $pairs['signing']['private_key'],
            $pairs['signing']['public_key'],
            $pairs['encryption']['algorithm'],
            $pairs['encryption']['public_key'],
        );

        $result = $service->decryptAndVerify(
            $envelope,
            $pairs['encryption']['algorithm'],
            $pairs['encryption']['private_key'],
        );

        $this->assertSame('mensaje firmado', $result['message']);
        $this->assertTrue($result['signature_valid']);
    }

    public function test_wrong_expected_signer_public_key_invalidates_signature(): void
    {
        $service = app(SignedEncryptionService::class);
        $pairs = $service->generatePairs();
        $other = $service->generatePairs();
        $envelope = $service->signAndEncrypt(
            'mensaje firmado',
            $pairs['signing']['algorithm'],
            $pairs['signing']['private_key'],
            $pairs['signing']['public_key'],
            $pairs['encryption']['algorithm'],
            $pairs['encryption']['public_key'],
        );

        $result = $service->decryptAndVerify(
            $envelope,
            $pairs['encryption']['algorithm'],
            $pairs['encryption']['private_key'],
            null,
            $other['signing']['public_key'],
        );

        $this->assertFalse($result['signature_valid']);
    }

    public function test_tampered_encrypted_package_fails_decryption(): void
    {
        $service = app(SignedEncryptionService::class);
        $pairs = $service->generatePairs();
        $envelope = $service->signAndEncrypt(
            'mensaje firmado',
            $pairs['signing']['algorithm'],
            $pairs['signing']['private_key'],
            $pairs['signing']['public_key'],
            $pairs['encryption']['algorithm'],
            $pairs['encryption']['public_key'],
        );
        $data = $envelope->toArray();
        $raw = base64_decode($data['ciphertext'], true);
        $raw[0] = chr(ord($raw[0]) ^ 0x01);
        $data['ciphertext'] = base64_encode($raw);

        $this->expectException(CryptoException::class);
        $service->decryptAndVerify(CryptoEnvelope::fromArray($data), $pairs['encryption']['algorithm'], $pairs['encryption']['private_key']);
    }
}
