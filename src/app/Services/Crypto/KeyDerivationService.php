<?php

declare(strict_types=1);

namespace App\Services\Crypto;

use App\Exceptions\CryptoException;
use SensitiveParameter;

final class KeyDerivationService
{
    /**
     * @return array{algorithm: string, key: string, key_hex: string, salt: string, iterations: int, bytes: int}
     */
    public function pbkdf2(
        #[SensitiveParameter] string $passphrase,
        ?string $salt = null,
        int $iterations = 200_000,
        int $bytes = 32,
    ): array {
        $this->validateBytes($bytes);
        $rawSalt = $salt !== null ? KeyCodec::decode($salt, 16, 'El salt') : random_bytes(16);
        $rawKey = hash_pbkdf2('sha256', $passphrase, $rawSalt, max(1, $iterations), $bytes, true);

        return [
            'algorithm' => 'pbkdf2-sha256',
            'key' => base64_encode($rawKey),
            'key_hex' => bin2hex($rawKey),
            'salt' => base64_encode($rawSalt),
            'iterations' => $iterations,
            'bytes' => $bytes,
        ];
    }

    /**
     * @return array{algorithm: string, key: string, key_hex: string, salt: string, opslimit: int, memlimit: int, bytes: int}
     */
    public function argon2id(
        #[SensitiveParameter] string $passphrase,
        ?string $salt = null,
        int $bytes = 32,
    ): array {
        $this->validateBytes($bytes);
        $rawSalt = $salt !== null ? KeyCodec::decode($salt, SODIUM_CRYPTO_PWHASH_SALTBYTES, 'El salt') : random_bytes(SODIUM_CRYPTO_PWHASH_SALTBYTES);

        $rawKey = sodium_crypto_pwhash(
            $bytes,
            $passphrase,
            $rawSalt,
            SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
            SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE,
            SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13,
        );

        return [
            'algorithm' => 'argon2id',
            'key' => base64_encode($rawKey),
            'key_hex' => bin2hex($rawKey),
            'salt' => base64_encode($rawSalt),
            'opslimit' => SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
            'memlimit' => SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE,
            'bytes' => $bytes,
        ];
    }

    private function validateBytes(int $bytes): void
    {
        if (! in_array($bytes, [16, 32], true)) {
            throw new CryptoException('La derivación solo admite llaves de 16 o 32 bytes.');
        }
    }
}
