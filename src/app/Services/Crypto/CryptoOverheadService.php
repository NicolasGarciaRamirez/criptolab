<?php

declare(strict_types=1);

namespace App\Services\Crypto;

use App\Services\Crypto\Registries\AsymmetricCipherRegistry;
use App\Services\Crypto\Registries\SignerRegistry;
use App\Services\Crypto\Registries\SymmetricCipherRegistry;

final class CryptoOverheadService
{
    public function __construct(
        private readonly SymmetricCipherRegistry $symmetric,
        private readonly AsymmetricCipherRegistry $asymmetric,
        private readonly SignerRegistry $signers,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function rows(): array
    {
        $rows = [];

        foreach ($this->symmetric->all() as $cipher) {
            $metadata = $cipher->metadata();
            $nonce = $metadata['nonce_bytes'] ?? $metadata['iv_bytes'] ?? 0;
            $tag = $metadata['tag_bytes'] ?? 0;
            $rows[] = [
                'family' => 'Simétrico',
                'algorithm' => $cipher->label(),
                'key_bytes' => $cipher->keyBytes(),
                'nonce_bytes' => $nonce,
                'tag_bytes' => $tag,
                'authenticated' => (bool) ($metadata['authenticated'] ?? false),
                'sample_100b' => 100 + $tag,
                'overhead_100b' => $nonce + $tag,
                'note' => ($metadata['authenticated'] ?? false) ? 'Autenticado' : 'Sin autenticación',
            ];
        }

        foreach ($this->asymmetric->all() as $cipher) {
            $metadata = $cipher->metadata();
            $bits = (int) ($metadata['bits'] ?? ($metadata['rsa_bits'] ?? 2048));
            $max = $cipher->maxPlaintextBytes();
            $isSealed = $cipher->id() === 'sealed-box';
            $isHybrid = $cipher->id() === 'hybrid-rsa-aes-256-gcm';
            $ciphertext = $isSealed ? 148 : ($isHybrid ? 372 : intdiv($bits, 8));
            $rows[] = [
                'family' => 'Asimétrico',
                'algorithm' => $cipher->label(),
                'key_bytes' => $isSealed ? 32 : intdiv($bits, 8),
                'nonce_bytes' => 0,
                'tag_bytes' => $isSealed ? 16 : ($isHybrid ? 16 : 0),
                'authenticated' => true,
                'sample_100b' => $ciphertext,
                'overhead_100b' => max(0, $ciphertext - 100),
                'note' => $isSealed ? 'X25519 + MAC (48 B de overhead)' : 'Máximo claro: '.$max.' bytes',
            ];
        }

        foreach ($this->signers->all() as $signer) {
            $metadata = $signer->metadata();
            $bits = (int) ($metadata['bits'] ?? 0);
            $signature = match ($signer->id()) {
                'ed25519' => 64,
                'ecdsa-p256' => 64,
                'ecdsa-p384' => 96,
                default => $bits > 0 ? intdiv($bits, 8) : 64,
            };
            $rows[] = [
                'family' => 'Firma',
                'algorithm' => $signer->label(),
                'key_bytes' => $signer->id() === 'ed25519' ? 32 : ($bits > 0 ? intdiv($bits, 8) : 32),
                'nonce_bytes' => 0,
                'tag_bytes' => 0,
                'authenticated' => true,
                'sample_100b' => 100 + $signature,
                'overhead_100b' => $signature,
                'note' => 'Firma estimada: '.$signature.' bytes',
            ];
        }

        return $rows;
    }
}
