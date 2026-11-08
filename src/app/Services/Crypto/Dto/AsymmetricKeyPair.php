<?php

declare(strict_types=1);

namespace App\Services\Crypto\Dto;

final readonly class AsymmetricKeyPair
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public string $algorithm,
        public string $publicKey,
        public string $privateKey,
        public array $meta = [],
    ) {}
}
