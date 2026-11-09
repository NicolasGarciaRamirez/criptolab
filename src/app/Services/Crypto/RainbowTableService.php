<?php

declare(strict_types=1);

namespace App\Services\Crypto;

use App\Services\Crypto\Hash\PasswordHasher;
use SensitiveParameter;

final class RainbowTableService
{
    /**
     * Diccionario educativo pequeño. No representa contraseñas reales.
     *
     * @var list<string>
     */
    private const PASSWORDS = [
        '123456', 'password', 'qwerty', 'admin', 'letmein', 'welcome',
        '12345678', 'abc123', 'iloveyou', 'monkey', 'dragon', 'master',
        'sunshine', 'princess', 'football', 'shadow', 'superman', 'michael',
        'trustno1', 'whatever',
    ];

    public function __construct(private readonly PasswordHasher $hasher) {}

    /**
     * @return array{found: bool, password: string, md5: string, sha1: string, sha256: string, position: int|null, size: int}
     */
    public function lookup(#[SensitiveParameter] string $password): array
    {
        $position = array_search($password, self::PASSWORDS, true);

        return [
            'found' => $position !== false,
            'password' => $password,
            'md5' => hash('md5', $password),
            'sha1' => hash('sha1', $password),
            'sha256' => hash('sha256', $password),
            'position' => $position === false ? null : $position + 1,
            'size' => count(self::PASSWORDS),
        ];
    }

    /**
     * @return array{hash: string, algorithm: string, different_each_time: bool, info: array<string, mixed>}
     */
    public function argon2id(#[SensitiveParameter] string $password): array
    {
        $first = $this->hasher->argon2id($password);
        $second = $this->hasher->argon2id($password);

        return [
            'hash' => $first['hash'],
            'algorithm' => 'argon2id',
            'different_each_time' => ! hash_equals($first['hash'], $second['hash']),
            'info' => password_get_info($first['hash']),
        ];
    }

    /**
     * @return list<string>
     */
    public function dictionary(): array
    {
        return self::PASSWORDS;
    }
}
