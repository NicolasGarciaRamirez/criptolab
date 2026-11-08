<?php

declare(strict_types=1);

namespace App\Services\Crypto\Hash;

use App\Exceptions\CryptoException;
use SensitiveParameter;

final class HashService
{
    /**
     * @var array<string, array{label: string, hash: string, bytes: int}>
     */
    private const ALGORITHMS = [
        'sha256' => ['label' => 'SHA-256', 'hash' => 'sha256', 'bytes' => 32],
        'sha512' => ['label' => 'SHA-512', 'hash' => 'sha512', 'bytes' => 64],
        'sha3-256' => ['label' => 'SHA3-256', 'hash' => 'sha3-256', 'bytes' => 32],
        'blake2b512' => ['label' => 'BLAKE2b-512', 'hash' => 'sodium-generichash', 'bytes' => 64],
    ];

    /**
     * @return array<string, array{label: string, hash: string, bytes: int}>
     */
    public function available(): array
    {
        return self::ALGORITHMS;
    }

    /**
     * @return array{algorithm: string, label: string, hex: string, base64: string, bytes: int}
     */
    public function digest(string $algorithm, string $text): array
    {
        $config = $this->algorithm($algorithm);
        $raw = $config['hash'] === 'sodium-generichash'
            ? sodium_crypto_generichash($text, '', $config['bytes'])
            : hash($config['hash'], $text, true);

        return [
            'algorithm' => $algorithm,
            'label' => $config['label'],
            'hex' => bin2hex($raw),
            'base64' => base64_encode($raw),
            'bytes' => strlen($raw),
        ];
    }

    /**
     * @return array{algorithm: string, hex: string, base64: string, bytes: int, key_bytes: int}
     */
    public function hmac(string $text, #[SensitiveParameter] string $key): array
    {
        $raw = hash_hmac('sha256', $text, $key, true);

        return [
            'algorithm' => 'hmac-sha256',
            'hex' => bin2hex($raw),
            'base64' => base64_encode($raw),
            'bytes' => strlen($raw),
            'key_bytes' => strlen($key),
        ];
    }

    public function verifyHmac(string $text, #[SensitiveParameter] string $key, string $expectedHex): bool
    {
        $actual = hash_hmac('sha256', $text, $key);

        return hash_equals($actual, strtolower(trim($expectedHex)));
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array{algorithm: string, hash: string, info: array<string, mixed>, salt_included: bool, cost: mixed}
     */
    public function passwordHash(string $algorithm, #[SensitiveParameter] string $password, array $options = []): array
    {
        if ($algorithm === 'bcrypt') {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => (int) ($options['cost'] ?? 12)]);
        } elseif ($algorithm === 'argon2id') {
            if (! in_array('argon2id', password_algos(), true)) {
                throw new CryptoException('Argon2id no está disponible en este PHP.');
            }

            $hash = password_hash($password, PASSWORD_ARGON2ID, [
                'memory_cost' => (int) ($options['memory_cost'] ?? 65536),
                'time_cost' => (int) ($options['time_cost'] ?? 4),
                'threads' => (int) ($options['threads'] ?? 2),
            ]);
        } else {
            throw CryptoException::invalidAlgorithm($algorithm);
        }

        $info = password_get_info($hash);

        return [
            'algorithm' => $algorithm,
            'hash' => $hash,
            'info' => $info,
            'salt_included' => true,
            'cost' => $info['options']['cost'] ?? $info['options'] ?? null,
        ];
    }

    public function verifyPassword(#[SensitiveParameter] string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * @return array<string, mixed>
     */
    private function algorithm(string $algorithm): array
    {
        return self::ALGORITHMS[$algorithm] ?? throw CryptoException::invalidAlgorithm($algorithm);
    }
}
