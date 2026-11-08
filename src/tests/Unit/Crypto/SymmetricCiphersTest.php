<?php

namespace Tests\Unit\Crypto;

use App\Exceptions\CryptoException;
use App\Services\Crypto\Contracts\SymmetricCipher;
use App\Services\Crypto\Dto\CryptoEnvelope;
use App\Services\Crypto\KeyCodec;
use App\Services\Crypto\Registries\SymmetricCipherRegistry;
use Tests\TestCase;

class SymmetricCiphersTest extends TestCase
{
    public function test_every_symmetric_cipher_roundtrips(): void
    {
        foreach (app(SymmetricCipherRegistry::class)->all() as $cipher) {
            $key = $cipher->generateKey();
            $envelope = $cipher->encrypt('Hola CriptoLab, texto de prueba.', $key);

            $this->assertSame('Hola CriptoLab, texto de prueba.', $cipher->decrypt($envelope, $key), $cipher->id());
            $this->assertSame($cipher->id(), $envelope->algorithm);
            $this->assertNotSame('', $envelope->ciphertext);
        }
    }

    public function test_encrypting_the_same_text_twice_produces_different_iv_and_ciphertext(): void
    {
        foreach (app(SymmetricCipherRegistry::class)->all() as $cipher) {
            $key = $cipher->generateKey();
            $first = $cipher->encrypt('mismo texto', $key);
            $second = $cipher->encrypt('mismo texto', $key);

            $this->assertNotSame($first->iv, $second->iv, $cipher->id());
            $this->assertNotSame($first->ciphertext, $second->ciphertext, $cipher->id());
        }
    }

    public function test_authenticated_ciphers_reject_tampered_ciphertext(): void
    {
        foreach (['aes-256-gcm', 'chacha20-poly1305', 'xsalsa20-poly1305'] as $id) {
            $cipher = app(SymmetricCipherRegistry::class)->get($id);
            $key = $cipher->generateKey();
            $envelope = $cipher->encrypt('texto autenticado', $key);

            $raw = base64_decode($envelope->ciphertext, true);
            $raw[0] = chr(ord($raw[0]) ^ 0x01);

            $tampered = new CryptoEnvelope(
                algorithm: $envelope->algorithm,
                ciphertext: base64_encode($raw),
                iv: $envelope->iv,
                tag: $envelope->tag,
                meta: $envelope->meta,
            );

            try {
                $cipher->decrypt($tampered, $key);
                $this->fail("El cifrado {$id} aceptó un texto manipulado.");
            } catch (CryptoException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_wrong_key_is_rejected_by_gcm(): void
    {
        $cipher = app(SymmetricCipherRegistry::class)->get('aes-256-gcm');
        $envelope = $cipher->encrypt('secreto', $cipher->generateKey());

        $this->expectException(CryptoException::class);
        $cipher->decrypt($envelope, $cipher->generateKey());
    }

    public function test_keys_can_be_provided_as_hex_and_are_normalized(): void
    {
        $cipher = app(SymmetricCipherRegistry::class)->get('aes-256-gcm');
        $rawKey = random_bytes(32);
        $hexKey = bin2hex($rawKey);

        $envelope = $cipher->encrypt('texto', $hexKey);

        $this->assertSame('texto', $cipher->decrypt($envelope, base64_encode($rawKey)));
    }

    public function test_keycodec_rejects_wrong_length(): void
    {
        $this->expectException(CryptoException::class);
        KeyCodec::decode(base64_encode('corta'), 32);
    }

    public function test_cipher_metadata_reports_key_size_and_authentication(): void
    {
        /** @var SymmetricCipher $gcm */
        $gcm = app(SymmetricCipherRegistry::class)->get('aes-256-gcm');
        $this->assertSame(32, $gcm->keyBytes());
        $this->assertTrue($gcm->metadata()['authenticated']);

        /** @var SymmetricCipher $cbc */
        $cbc = app(SymmetricCipherRegistry::class)->get('aes-256-cbc');
        $this->assertFalse($cbc->metadata()['authenticated']);
    }

    public function test_envelope_json_roundtrip_and_combined_representation(): void
    {
        $cipher = app(SymmetricCipherRegistry::class)->get('aes-256-gcm');
        $key = $cipher->generateKey();
        $envelope = $cipher->encrypt('json', $key);

        $restored = CryptoEnvelope::fromJson($envelope->toJson());

        $this->assertSame('json', $cipher->decrypt($restored, $key));
        $this->assertNotSame('', $envelope->combined());
        $this->assertSame(4, $envelope->sizes()['ciphertext']);
    }
}
