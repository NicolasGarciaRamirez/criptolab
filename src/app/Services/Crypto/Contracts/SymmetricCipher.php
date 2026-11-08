<?php

declare(strict_types=1);

namespace App\Services\Crypto\Contracts;

use App\Services\Crypto\Dto\CryptoEnvelope;

interface SymmetricCipher
{
    public function id(): string;

    public function label(): string;

    public function description(): string;

    public function keyBytes(): int;

    public function nonceBytes(): int;

    public function generateKey(): string;

    public function encrypt(string $plaintext, string $key): CryptoEnvelope;

    public function decrypt(CryptoEnvelope $envelope, string $key): string;

    /**
     * @return array<string, mixed>
     */
    public function metadata(): array;
}
