<?php

declare(strict_types=1);

namespace App\Services\Crypto\Contracts;

use App\Services\Crypto\Dto\AsymmetricKeyPair;
use App\Services\Crypto\Dto\CryptoEnvelope;

interface AsymmetricCipher
{
    public function id(): string;

    public function label(): string;

    public function description(): string;

    /**
     * @param  array<string, mixed>  $options
     */
    public function generateKeyPair(array $options = []): AsymmetricKeyPair;

    public function encrypt(string $plaintext, string $publicKey): CryptoEnvelope;

    public function decrypt(CryptoEnvelope $envelope, string $privateKey, ?string $passphrase = null): string;

    public function maxPlaintextBytes(): int;

    /**
     * @return array<string, mixed>
     */
    public function metadata(): array;
}
