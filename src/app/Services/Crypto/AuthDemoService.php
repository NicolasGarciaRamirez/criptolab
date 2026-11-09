<?php

declare(strict_types=1);

namespace App\Services\Crypto;

use App\Exceptions\CryptoException;
use App\Models\CryptoDemoEntry;
use App\Models\CryptoKeypair;
use App\Models\User;
use App\Services\Crypto\Dto\CryptoEnvelope;
use App\Services\Crypto\Registries\AsymmetricCipherRegistry;
use App\Services\Crypto\Registries\SymmetricCipherRegistry;
use SensitiveParameter;

final class AuthDemoService
{
    public function __construct(
        private readonly SymmetricCipherRegistry $symmetric,
        private readonly AsymmetricCipherRegistry $asymmetric,
        private readonly KeypairService $keypairs,
        private readonly MasterKeyService $masterKey,
    ) {}

    /**
     * Crea un registro de demostración. Por defecto el usuario se cifra con
     * criptografía simétrica y la contraseña con asimétrica; swap invierte
     * las familias.
     *
     * @param  array{username: string, password: string, sym_algorithm: string, asym_algorithm: string, swap?: bool}  $data
     */
    public function create(User $user, #[SensitiveParameter] array $data): CryptoDemoEntry
    {
        $swap = (bool) ($data['swap'] ?? false);
        $symmetric = $this->symmetric->get($data['sym_algorithm']);
        $asymmetric = $this->asymmetric->get($data['asym_algorithm']);
        $keypair = $this->keypairs->ensureForUser($user, $asymmetric->id());

        $symmetricKey = $symmetric->generateKey();

        $usernameEnvelope = $swap
            ? $asymmetric->encrypt($data['username'], $keypair->public_key)
            : $symmetric->encrypt($data['username'], $symmetricKey);

        $passwordEnvelope = $swap
            ? $symmetric->encrypt($data['password'], $symmetricKey)
            : $asymmetric->encrypt($data['password'], $keypair->public_key);

        return CryptoDemoEntry::create([
            'user_id' => $user->id,
            'username_plain' => $data['username'],
            'username_encrypted' => $usernameEnvelope->toJson(),
            'password_plain' => $data['password'],
            'password_encrypted' => $passwordEnvelope->toJson(),
            'sym_algorithm' => $symmetric->id(),
            'asym_algorithm' => $asymmetric->id(),
            'meta' => [
                'swap' => $swap,
                'symmetric_key_encrypted' => $this->masterKey->encrypt($symmetricKey),
                'keypair_id' => $keypair->id,
                'username_family' => $swap ? 'asymmetric' : 'symmetric',
                'password_family' => $swap ? 'symmetric' : 'asymmetric',
            ],
        ]);
    }

    /**
     * Descifra un registro usando las llaves guardadas del propietario.
     *
     * @return array{username: string, password: string}
     */
    public function decrypt(CryptoDemoEntry $entry): array
    {
        $meta = $entry->meta ?? [];
        $symmetricKey = $meta['symmetric_key_encrypted'] ?? null;
        $keypairId = $meta['keypair_id'] ?? null;

        if (! is_string($symmetricKey) || ! is_int($keypairId)) {
            throw new CryptoException('El registro no tiene la información de llaves necesaria.');
        }

        $keypair = CryptoKeypair::query()
            ->where('user_id', $entry->user_id)
            ->find($keypairId);

        if ($keypair === null) {
            throw new CryptoException('La llave usada por este registro ya no existe.');
        }

        $symmetric = $this->symmetric->get($entry->sym_algorithm);
        $asymmetric = $this->asymmetric->get($entry->asym_algorithm);
        $rawSymmetricKey = $this->masterKey->decrypt($symmetricKey);
        $privateKey = $this->keypairs->privateKey($keypair);

        $usernameEnvelope = CryptoEnvelope::fromJson($entry->username_encrypted);
        $passwordEnvelope = CryptoEnvelope::fromJson($entry->password_encrypted);
        $swapped = (bool) ($meta['swap'] ?? false);

        return [
            'username' => $swapped
                ? $asymmetric->decrypt($usernameEnvelope, $privateKey)
                : $symmetric->decrypt($usernameEnvelope, $rawSymmetricKey),
            'password' => $swapped
                ? $symmetric->decrypt($passwordEnvelope, $rawSymmetricKey)
                : $asymmetric->decrypt($passwordEnvelope, $privateKey),
        ];
    }

    public function reencrypt(CryptoDemoEntry $entry): CryptoDemoEntry
    {
        $plain = $this->decrypt($entry);
        $entry->loadMissing('user');

        return $this->create($entry->user, [
            'username' => $plain['username'],
            'password' => $plain['password'],
            'sym_algorithm' => $entry->sym_algorithm,
            'asym_algorithm' => $entry->asym_algorithm,
            'swap' => (bool) ($entry->meta['swap'] ?? false),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function export(User $user): array
    {
        return $user->cryptoDemoEntries()
            ->latest('id')
            ->get()
            ->map(fn (CryptoDemoEntry $entry): array => [
                'id' => $entry->id,
                'username_plain' => $entry->username_plain,
                'username_encrypted' => $entry->username_encrypted,
                'password_plain' => $entry->password_plain,
                'password_encrypted' => $entry->password_encrypted,
                'sym_algorithm' => $entry->sym_algorithm,
                'asym_algorithm' => $entry->asym_algorithm,
                'swap' => (bool) ($entry->meta['swap'] ?? false),
                'created_at' => $entry->created_at?->toIso8601String(),
                'aviso' => 'Exportación de demostración. No usar contraseñas reales.',
            ])
            ->all();
    }
}
