<?php

declare(strict_types=1);

namespace App\Services\Crypto\Registries;

use App\Exceptions\CryptoException;
use App\Services\Crypto\Contracts\AsymmetricCipher;

final class AsymmetricCipherRegistry
{
    /**
     * @param  array<string, AsymmetricCipher>  $ciphers
     */
    public function __construct(private readonly array $ciphers) {}

    public function get(string $id): AsymmetricCipher
    {
        return $this->ciphers[$id] ?? throw CryptoException::invalidAlgorithm($id);
    }

    /**
     * @return array<string, AsymmetricCipher>
     */
    public function all(): array
    {
        return $this->ciphers;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function catalog(): array
    {
        return array_values(array_map(
            fn (AsymmetricCipher $cipher): array => [
                'id' => $cipher->id(),
                'label' => $cipher->label(),
                'description' => $cipher->description(),
                'metadata' => $cipher->metadata(),
            ],
            $this->ciphers,
        ));
    }
}
