<?php

declare(strict_types=1);

namespace App\Services\Crypto;

use RuntimeException;
use SensitiveParameter;

final class MasterKeyService
{
    private string $key;

    public function __construct()
    {
        $this->key = self::decodeKey(
            config('crypto.master_key'),
            'CRYPTO_MASTER_KEY',
        );
    }

    /**
     * Cifra un texto con XSalsa20-Poly1305 usando una nonce aleatoria.
     */
    public function encrypt(#[SensitiveParameter] string $plaintext): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = sodium_crypto_secretbox($plaintext, $nonce, $this->key);

        return 'v1:'.base64_encode($nonce.$ciphertext);
    }

    /**
     * Descifra un texto generado por encrypt() y detecta manipulaciones.
     */
    public function decrypt(#[SensitiveParameter] string $payload): string
    {
        if (! str_starts_with($payload, 'v1:')) {
            throw new RuntimeException('Formato de cifrado maestro no reconocido.');
        }

        $raw = base64_decode(substr($payload, 3), true);

        if ($raw === false || strlen($raw) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            throw new RuntimeException('El texto cifrado con la llave maestra está corrupto.');
        }

        $nonce = substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $plaintext = sodium_crypto_secretbox_open($ciphertext, $nonce, $this->key);

        if ($plaintext === false) {
            throw new RuntimeException('No se pudo descifrar con la llave maestra actual.');
        }

        return $plaintext;
    }

    public static function generateKey(): string
    {
        return 'base64:'.base64_encode(random_bytes(32));
    }

    public static function decodeKey(?string $value, string $name): string
    {
        if ($value === null || $value === '') {
            throw new RuntimeException("Falta {$name} en el archivo .env.");
        }

        $encoded = str_starts_with($value, 'base64:') ? substr($value, 7) : $value;
        $key = base64_decode($encoded, true);

        if ($key === false || strlen($key) !== 32) {
            throw new RuntimeException("{$name} debe ser una llave de 32 bytes codificada en Base64.");
        }

        return $key;
    }
}
