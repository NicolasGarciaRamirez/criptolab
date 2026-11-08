<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class CryptoException extends RuntimeException
{
    public static function invalidKey(string $algorithm): self
    {
        return new self("La llave no es válida para {$algorithm}.");
    }

    public static function invalidKeyMaterial(string $name, int $bytes): self
    {
        return new self("{$name} debe tener {$bytes} bytes (Base64 o hexadecimal).");
    }

    public static function invalidSignature(string $algorithm): self
    {
        return new self("La firma no es válida para {$algorithm}.");
    }

    public static function decryptionFailed(string $algorithm): self
    {
        return new self("No se pudo descifrar. Verifica la llave, el IV/nonce o si el texto fue alterado ({$algorithm}).");
    }

    public static function invalidPayload(string $algorithm): self
    {
        return new self("El texto cifrado no tiene el formato esperado para {$algorithm}.");
    }

    public static function messageTooLong(string $algorithm, int $maxBytes): self
    {
        return new self("El mensaje es demasiado largo para {$algorithm}. Máximo: {$maxBytes} bytes. Usa el cifrado híbrido para textos largos.");
    }

    public static function invalidAlgorithm(string $algorithm): self
    {
        return new self("Algoritmo no soportado: {$algorithm}.");
    }
}
