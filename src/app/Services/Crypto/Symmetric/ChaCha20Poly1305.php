<?php

declare(strict_types=1);

namespace App\Services\Crypto\Symmetric;

use App\Exceptions\CryptoException;
use App\Services\Crypto\Contracts\SymmetricCipher;
use App\Services\Crypto\Dto\CryptoEnvelope;
use App\Services\Crypto\KeyCodec;

final class ChaCha20Poly1305 implements SymmetricCipher
{
    private const KEY_BYTES = SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_KEYBYTES;

    private const NONCE_BYTES = SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_NPUBBYTES;

    private const TAG_BYTES = SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_ABYTES;

    public function id(): string
    {
        return 'chacha20-poly1305';
    }

    public function label(): string
    {
        return 'ChaCha20-Poly1305';
    }

    public function description(): string
    {
        return 'Cifrado autenticado moderno de Sodium, ideal cuando no hay aceleración AES por hardware.';
    }

    public function keyBytes(): int
    {
        return self::KEY_BYTES;
    }

    public function nonceBytes(): int
    {
        return self::NONCE_BYTES;
    }

    public function generateKey(): string
    {
        return KeyCodec::random(self::KEY_BYTES);
    }

    public function encrypt(string $plaintext, string $key): CryptoEnvelope
    {
        $rawKey = KeyCodec::decode($key, self::KEY_BYTES);
        $nonce = random_bytes(self::NONCE_BYTES);

        $sealed = sodium_crypto_aead_chacha20poly1305_ietf_encrypt(
            $plaintext,
            '',
            $nonce,
            $rawKey,
        );

        $tag = substr($sealed, -self::TAG_BYTES);
        $ciphertext = substr($sealed, 0, -self::TAG_BYTES);

        return new CryptoEnvelope(
            algorithm: $this->id(),
            ciphertext: base64_encode($ciphertext),
            iv: base64_encode($nonce),
            tag: base64_encode($tag),
            meta: ['key_bytes' => self::KEY_BYTES, 'tag_bytes' => self::TAG_BYTES],
        );
    }

    public function decrypt(CryptoEnvelope $envelope, string $key): string
    {
        if ($envelope->algorithm !== $this->id()) {
            throw CryptoException::invalidAlgorithm($envelope->algorithm);
        }

        $rawKey = KeyCodec::decode($key, self::KEY_BYTES);
        $nonce = base64_decode((string) $envelope->iv, true);
        $tag = base64_decode((string) $envelope->tag, true);
        $ciphertext = base64_decode($envelope->ciphertext, true);

        if ($nonce === false || strlen($nonce) !== self::NONCE_BYTES || $tag === false || strlen($tag) !== self::TAG_BYTES || $ciphertext === false) {
            throw CryptoException::invalidPayload($this->id());
        }

        $plaintext = sodium_crypto_aead_chacha20poly1305_ietf_decrypt(
            $ciphertext.$tag,
            '',
            $nonce,
            $rawKey,
        );

        if ($plaintext === false) {
            throw CryptoException::decryptionFailed($this->id());
        }

        return $plaintext;
    }

    public function metadata(): array
    {
        return [
            'key_bytes' => self::KEY_BYTES,
            'nonce_bytes' => self::NONCE_BYTES,
            'tag_bytes' => self::TAG_BYTES,
            'authenticated' => true,
            'recommended' => true,
        ];
    }
}
