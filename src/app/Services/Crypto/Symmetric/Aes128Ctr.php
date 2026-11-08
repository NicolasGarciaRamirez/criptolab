<?php

declare(strict_types=1);

namespace App\Services\Crypto\Symmetric;

use App\Exceptions\CryptoException;
use App\Services\Crypto\Contracts\SymmetricCipher;
use App\Services\Crypto\Dto\CryptoEnvelope;
use App\Services\Crypto\KeyCodec;

final class Aes128Ctr implements SymmetricCipher
{
    private const KEY_BYTES = 16;

    private const IV_BYTES = 16;

    public function id(): string
    {
        return 'aes-128-ctr';
    }

    public function label(): string
    {
        return 'AES-128-CTR';
    }

    public function description(): string
    {
        return 'Variante de comparación con llave de 128 bits. Convierte AES en un cifrado de flujo; no autentica.';
    }

    public function keyBytes(): int
    {
        return self::KEY_BYTES;
    }

    public function nonceBytes(): int
    {
        return self::IV_BYTES;
    }

    public function generateKey(): string
    {
        return KeyCodec::random(self::KEY_BYTES);
    }

    public function encrypt(string $plaintext, string $key): CryptoEnvelope
    {
        $rawKey = KeyCodec::decode($key, self::KEY_BYTES);
        $iv = random_bytes(self::IV_BYTES);

        $ciphertext = openssl_encrypt(
            $plaintext,
            'aes-128-ctr',
            $rawKey,
            OPENSSL_RAW_DATA,
            $iv,
        );

        if ($ciphertext === false) {
            throw new CryptoException('No se pudo cifrar con AES-128-CTR.');
        }

        return new CryptoEnvelope(
            algorithm: $this->id(),
            ciphertext: base64_encode($ciphertext),
            iv: base64_encode($iv),
            meta: ['key_bytes' => self::KEY_BYTES, 'iv_bytes' => self::IV_BYTES, 'authenticated' => false],
        );
    }

    public function decrypt(CryptoEnvelope $envelope, string $key): string
    {
        if ($envelope->algorithm !== $this->id()) {
            throw CryptoException::invalidAlgorithm($envelope->algorithm);
        }

        $rawKey = KeyCodec::decode($key, self::KEY_BYTES);
        $iv = base64_decode((string) $envelope->iv, true);
        $ciphertext = base64_decode($envelope->ciphertext, true);

        if ($iv === false || strlen($iv) !== self::IV_BYTES || $ciphertext === false) {
            throw CryptoException::invalidPayload($this->id());
        }

        $plaintext = openssl_decrypt(
            $ciphertext,
            'aes-128-ctr',
            $rawKey,
            OPENSSL_RAW_DATA,
            $iv,
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
            'iv_bytes' => self::IV_BYTES,
            'authenticated' => false,
            'recommended' => false,
        ];
    }
}
