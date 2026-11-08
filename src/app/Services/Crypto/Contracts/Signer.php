<?php

declare(strict_types=1);

namespace App\Services\Crypto\Contracts;

use App\Services\Crypto\Dto\AsymmetricKeyPair;

interface Signer
{
    public function id(): string;

    public function label(): string;

    public function description(): string;

    public function generateKeyPair(): AsymmetricKeyPair;

    public function sign(string $message, string $privateKey, ?string $passphrase = null): string;

    public function verify(string $message, string $signature, string $publicKey): bool;

    /**
     * @return array<string, mixed>
     */
    public function metadata(): array;
}
