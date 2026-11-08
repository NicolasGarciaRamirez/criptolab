<?php

declare(strict_types=1);

namespace App\Services\Crypto;

use App\Exceptions\CryptoException;

final class KeyCodec
{
    /**
     * Acepta Base64, Base64 con prefijo "base64:" o hexadecimal.
     */
    public static function decode(string $value, int $bytes, string $name = 'La llave'): string
    {
        $value = trim($value);

        if ($value === '') {
            throw CryptoException::invalidKeyMaterial($name, $bytes);
        }

        if (str_starts_with($value, 'base64:')) {
            $value = substr($value, 7);
        }

        if (preg_match('/^[0-9a-fA-F]{'.($bytes * 2).'}$/', $value) === 1) {
            $decoded = hex2bin($value);

            if ($decoded !== false && strlen($decoded) === $bytes) {
                return $decoded;
            }
        }

        $decoded = base64_decode($value, true);

        if ($decoded === false || strlen($decoded) !== $bytes) {
            throw CryptoException::invalidKeyMaterial($name, $bytes);
        }

        return $decoded;
    }

    public static function encode(string $raw): string
    {
        return base64_encode($raw);
    }

    public static function hex(string $raw): string
    {
        return bin2hex($raw);
    }

    public static function random(int $bytes): string
    {
        return base64_encode(random_bytes($bytes));
    }
}
