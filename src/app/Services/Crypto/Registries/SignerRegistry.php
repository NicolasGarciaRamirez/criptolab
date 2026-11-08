<?php

declare(strict_types=1);

namespace App\Services\Crypto\Registries;

use App\Exceptions\CryptoException;
use App\Services\Crypto\Contracts\Signer;

final class SignerRegistry
{
    /**
     * @param  array<string, Signer>  $signers
     */
    public function __construct(private readonly array $signers) {}

    public function get(string $id): Signer
    {
        return $this->signers[$id] ?? throw CryptoException::invalidAlgorithm($id);
    }

    /**
     * @return array<string, Signer>
     */
    public function all(): array
    {
        return $this->signers;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function catalog(): array
    {
        return array_values(array_map(
            fn (Signer $signer): array => [
                'id' => $signer->id(),
                'label' => $signer->label(),
                'description' => $signer->description(),
                'metadata' => $signer->metadata(),
            ],
            $this->signers,
        ));
    }
}
