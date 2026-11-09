<?php

namespace Tests\Unit\Crypto;

use App\Exceptions\CryptoException;
use App\Services\Crypto\Dto\CryptoEnvelope;
use App\Services\Crypto\Symmetric\Aes256Gcm;
use Tests\TestCase;

class AadGcmTest extends TestCase
{
    public function test_aad_roundtrip_and_tamper_detection(): void
    {
        $aes = app(Aes256Gcm::class);
        $key = $aes->generateKey();
        $aad = 'usuario=42;contexto=login';

        $envelope = $aes->encryptWithAad('mensaje', $key, $aad);

        $this->assertSame('mensaje', $aes->decryptWithAad($envelope, $key, $aad));
        $this->assertArrayHasKey('aad', $envelope->meta);

        $this->expectException(CryptoException::class);
        $aes->decryptWithAad($envelope, $key, 'usuario=43;contexto=login');
    }

    public function test_aad_envelope_still_rejects_tampered_ciphertext(): void
    {
        $aes = app(Aes256Gcm::class);
        $key = $aes->generateKey();
        $envelope = $aes->encryptWithAad('mensaje', $key, 'aad');
        $data = $envelope->toArray();
        $raw = base64_decode($data['ciphertext'], true);
        $raw[0] = chr(ord($raw[0]) ^ 0x01);
        $data['ciphertext'] = base64_encode($raw);

        $this->expectException(CryptoException::class);
        $aes->decryptWithAad(CryptoEnvelope::fromArray($data), $key, 'aad');
    }
}
