<?php

declare(strict_types=1);

namespace App\Services\Crypto;

use App\Exceptions\CryptoException;
use App\Models\CryptoKeypair;
use App\Models\User;
use App\Services\Crypto\Dto\AsymmetricKeyPair;
use App\Services\Crypto\Registries\AsymmetricCipherRegistry;
use App\Services\Crypto\Registries\SignerRegistry;
use SensitiveParameter;

final class KeypairService
{
    public function __construct(
        private readonly AsymmetricCipherRegistry $ciphers,
        private readonly SignerRegistry $signers,
    ) {}

    /**
     * @return array{pair: AsymmetricKeyPair, keypair: CryptoKeypair|null, fingerprint: string}
     */
    public function generateCipherKeypair(
        string $algorithm,
        ?string $name,
        User $user,
        bool $save,
        #[SensitiveParameter] ?string $passphrase = null,
    ): array {
        $cipher = $this->ciphers->get($algorithm);
        $pair = $cipher->generateKeyPair(['passphrase' => $passphrase]);

        return $this->persist($pair, $name ?? $cipher->label(), $user, $save);
    }

    /**
     * @return array{pair: AsymmetricKeyPair, keypair: CryptoKeypair|null, fingerprint: string}
     */
    public function generateSignerKeypair(
        string $algorithm,
        ?string $name,
        User $user,
        bool $save,
    ): array {
        $signer = $this->signers->get($algorithm);
        $pair = $signer->generateKeyPair();

        return $this->persist($pair, $name ?? $signer->label(), $user, $save);
    }

    public function ensureForUser(User $user, string $algorithm): CryptoKeypair
    {
        $existing = CryptoKeypair::query()
            ->where('user_id', $user->id)
            ->where('algorithm', $algorithm)
            ->oldest('id')
            ->get()
            ->first(fn (CryptoKeypair $keypair): bool => ! str_contains(
                (string) $keypair->private_key_encrypted,
                'ENCRYPTED PRIVATE KEY',
            ));

        if ($existing !== null) {
            return $existing;
        }

        $cipher = $this->ciphers->get($algorithm);
        $pair = $cipher->generateKeyPair();

        $keypair = $this->persist($pair, 'Demo · '.$cipher->label(), $user, true)['keypair'];

        if ($keypair === null) {
            throw new CryptoException('No se pudo guardar el par de llaves.');
        }

        return $keypair;
    }

    public function privateKey(CryptoKeypair $keypair): string
    {
        return (string) $keypair->private_key_encrypted;
    }

    public function saveImported(
        User $user,
        string $algorithm,
        string $name,
        string $publicKey,
        #[SensitiveParameter] string $privateKey,
    ): CryptoKeypair {
        return CryptoKeypair::create([
            'user_id' => $user->id,
            'name' => $name,
            'algorithm' => $algorithm,
            'public_key' => $publicKey,
            'private_key_encrypted' => $privateKey,
            'fingerprint' => hash('sha256', $publicKey),
        ]);
    }

    public function delete(CryptoKeypair $keypair): void
    {
        $keypair->delete();
    }

    /**
     * @return array{pair: AsymmetricKeyPair, keypair: CryptoKeypair|null, fingerprint: string}
     */
    private function persist(AsymmetricKeyPair $pair, string $name, User $user, bool $save): array
    {
        $fingerprint = hash('sha256', $pair->publicKey);
        $model = null;

        if ($save) {
            $model = CryptoKeypair::create([
                'user_id' => $user->id,
                'name' => $name,
                'algorithm' => $pair->algorithm,
                'public_key' => $pair->publicKey,
                'private_key_encrypted' => $pair->privateKey,
                'fingerprint' => $fingerprint,
            ]);
        }

        return [
            'pair' => $pair,
            'keypair' => $model,
            'fingerprint' => $fingerprint,
        ];
    }

    public function findForUser(User $user, int $id): CryptoKeypair
    {
        $keypair = CryptoKeypair::query()
            ->where('user_id', $user->id)
            ->find($id);

        if ($keypair === null) {
            throw new CryptoException('La llave solicitada no existe.');
        }

        return $keypair;
    }
}
