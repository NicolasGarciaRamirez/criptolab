<?php

declare(strict_types=1);

namespace App\Services\Crypto\Registries;

use App\Exceptions\CryptoException;
use App\Services\Crypto\Contracts\SymmetricCipher;

final class SymmetricCipherRegistry
{
    /**
     * @param  array<string, SymmetricCipher>  $ciphers
     */
    public function __construct(private readonly array $ciphers) {}

    public function get(string $id): SymmetricCipher
    {
        return $this->ciphers[$id] ?? throw CryptoException::invalidAlgorithm($id);
    }

    /**
     * @return array<string, SymmetricCipher>
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
            fn (SymmetricCipher $cipher): array => [
                'id' => $cipher->id(),
                'label' => $cipher->label(),
                'description' => $cipher->description(),
                'metadata' => $cipher->metadata(),
            ],
            $this->ciphers,
        ));
    }
}
