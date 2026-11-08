<?php

namespace Tests\Unit\Crypto;

use App\Exceptions\CryptoException;
use App\Services\Crypto\Dto\CryptoEnvelope;
use App\Services\Crypto\Registries\AsymmetricCipherRegistry;
use Tests\TestCase;

class AsymmetricCiphersTest extends TestCase
{
    public function test_rsa_oaep_roundtrip_and_random_ciphertext(): void
    {
        $rsa = app(AsymmetricCipherRegistry::class)->get('rsa-oaep-2048');
        $pair = $rsa->generateKeyPair();

        $first = $rsa->encrypt('Contraseña de prueba', $pair->publicKey);
        $second = $rsa->encrypt('Contraseña de prueba', $pair->publicKey);

        $this->assertSame('Contraseña de prueba', $rsa->decrypt($first, $pair->privateKey));
        $this->assertNotSame($first->ciphertext, $second->ciphertext);
    }

    public function test_rsa_rejects_messages_over_the_oaep_limit(): void
    {
        $rsa = app(AsymmetricCipherRegistry::class)->get('rsa-oaep-2048');
        $pair = $rsa->generateKeyPair();

        $this->expectException(CryptoException::class);
        $rsa->encrypt(str_repeat('x', 215), $pair->publicKey);
    }

    public function test_rsa_rejects_wrong_private_key(): void
    {
        $rsa = app(AsymmetricCipherRegistry::class)->get('rsa-oaep-2048');
        $pair = $rsa->generateKeyPair();
        $other = $rsa->generateKeyPair();
        $envelope = $rsa->encrypt('secreto', $pair->publicKey);

        $this->expectException(CryptoException::class);
        $rsa->decrypt($envelope, $other->privateKey);
    }

    public function test_rsa_supports_passphrase_protected_private_keys(): void
    {
        $rsa = app(AsymmetricCipherRegistry::class)->get('rsa-oaep-2048');
        $pair = $rsa->generateKeyPair(['passphrase' => 'clave-de-prueba']);
        $envelope = $rsa->encrypt('protegido', $pair->publicKey);

        $this->assertSame('protegido', $rsa->decrypt($envelope, $pair->privateKey, 'clave-de-prueba'));

        $this->expectException(CryptoException::class);
        $rsa->decrypt($envelope, $pair->privateKey);
    }

    public function test_sealed_box_roundtrip_and_tamper_detection(): void
    {
        $box = app(AsymmetricCipherRegistry::class)->get('sealed-box');
        $pair = $box->generateKeyPair();
        $envelope = $box->encrypt('mensaje sellado', $pair->publicKey);

        $this->assertSame('mensaje sellado', $box->decrypt($envelope, $pair->privateKey));

        $raw = base64_decode($envelope->ciphertext, true);
        $raw[0] = chr(ord($raw[0]) ^ 0x01);
        $tampered = new CryptoEnvelope(
            algorithm: $envelope->algorithm,
            ciphertext: base64_encode($raw),
            meta: $envelope->meta,
        );

        $this->expectException(CryptoException::class);
        $box->decrypt($tampered, $pair->privateKey);
    }

    public function test_hybrid_encryption_supports_long_messages(): void
    {
        $hybrid = app(AsymmetricCipherRegistry::class)->get('hybrid-rsa-aes-256-gcm');
        $pair = $hybrid->generateKeyPair();
        $message = str_repeat('Mensaje largo para demostrar el cifrado híbrido. ', 20);

        $envelope = $hybrid->encrypt($message, $pair->publicKey);

        $this->assertSame($message, $hybrid->decrypt($envelope, $pair->privateKey));
        $this->assertArrayHasKey('encrypted_key', $envelope->meta);
    }

    public function test_registry_contains_all_requested_algorithms(): void
    {
        $ids = array_keys(app(AsymmetricCipherRegistry::class)->all());

        $this->assertContains('rsa-oaep-2048', $ids);
        $this->assertContains('rsa-oaep-4096', $ids);
        $this->assertContains('sealed-box', $ids);
        $this->assertContains('hybrid-rsa-aes-256-gcm', $ids);
    }
}
