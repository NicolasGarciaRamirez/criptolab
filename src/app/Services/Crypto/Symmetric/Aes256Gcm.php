<?php

declare(strict_types=1);

namespace App\Services\Crypto\Symmetric;

use App\Exceptions\CryptoException;
use App\Services\Crypto\Contracts\SymmetricCipher;
use App\Services\Crypto\Dto\CryptoEnvelope;
use App\Services\Crypto\KeyCodec;

final class Aes256Gcm implements SymmetricCipher
{
    private const KEY_BYTES = 32;

    private const NONCE_BYTES = 12;

    private const TAG_BYTES = 16;

    public function id(): string
    {
        return 'aes-256-gcm';
    }

    public function label(): string
    {
        return 'AES-256-GCM';
    }

    public function description(): string
    {
        return 'Cifrado simétrico autenticado recomendado. Detecta cualquier alteración del texto o del tag.';
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
        $iv = random_bytes(self::NONCE_BYTES);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $plaintext,
            'aes-256-gcm',
            $rawKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            self::TAG_BYTES,
        );

        if ($ciphertext === false) {
            throw new CryptoException('No se pudo cifrar con AES-256-GCM.');
        }

        return new CryptoEnvelope(
            algorithm: $this->id(),
            ciphertext: base64_encode($ciphertext),
            iv: base64_encode($iv),
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
        $iv = base64_decode((string) $envelope->iv, true);
        $tag = base64_decode((string) $envelope->tag, true);
        $ciphertext = base64_decode($envelope->ciphertext, true);

        if ($iv === false || strlen($iv) !== self::NONCE_BYTES || $tag === false || strlen($tag) !== self::TAG_BYTES || $ciphertext === false) {
            throw CryptoException::invalidPayload($this->id());
        }

        $plaintext = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $rawKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
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
