<?php

declare(strict_types=1);

namespace App\Services\Crypto\Hash;

use App\Exceptions\CryptoException;
use SensitiveParameter;

final class PasswordHasher
{
    /**
     * @return array{algorithm: string, hash: string, cost: int}
     */
    public function bcrypt(#[SensitiveParameter] string $password, int $cost = 12): array
    {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => $cost]);

        return [
            'algorithm' => 'bcrypt',
            'hash' => $hash,
            'cost' => $cost,
        ];
    }

    /**
     * @return array{algorithm: string, hash: string, cost: array{memory_cost: int, time_cost: int, threads: int}}
     */
    public function argon2id(#[SensitiveParameter] string $password): array
    {
        if (! in_array('argon2id', password_algos(), true)) {
            throw new CryptoException('Argon2id no está disponible en este PHP.');
        }

        $options = [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 2,
        ];

        $hash = password_hash($password, PASSWORD_ARGON2ID, $options);

        return [
            'algorithm' => 'argon2id',
            'hash' => $hash,
            'cost' => $options,
        ];
    }

    public function verify(#[SensitiveParameter] string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}
