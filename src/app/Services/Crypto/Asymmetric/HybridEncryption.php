<?php

declare(strict_types=1);

namespace App\Services\Crypto\Asymmetric;

use App\Exceptions\CryptoException;
use App\Services\Crypto\Contracts\AsymmetricCipher;
use App\Services\Crypto\Dto\AsymmetricKeyPair;
use App\Services\Crypto\Dto\CryptoEnvelope;
use App\Services\Crypto\Symmetric\Aes256Gcm;
use SensitiveParameter;

final class HybridEncryption implements AsymmetricCipher
{
    public function __construct(
        private readonly Aes256Gcm $aes,
        private readonly RsaOaep $rsa,
    ) {}

    public function id(): string
    {
        return 'hybrid-rsa-aes-256-gcm';
    }

    public function label(): string
    {
        return 'Híbrido RSA + AES-256-GCM';
    }

    public function description(): string
    {
        return 'RSA cifra una llave AES temporal y AES cifra el mensaje. Permite textos largos superando el límite de RSA.';
    }

    public function generateKeyPair(array $options = []): AsymmetricKeyPair
    {
        $pair = $this->rsa->generateKeyPair($options);

        return new AsymmetricKeyPair(
            algorithm: $this->id(),
            publicKey: $pair->publicKey,
            privateKey: $pair->privateKey,
            meta: $pair->meta,
        );
    }

    public function encrypt(string $plaintext, string $publicKey): CryptoEnvelope
    {
        $symmetricKey = $this->aes->generateKey();
        $aesEnvelope = $this->aes->encrypt($plaintext, $symmetricKey);
        $encryptedKey = $this->rsa->encrypt($symmetricKey, $publicKey);

        return new CryptoEnvelope(
            algorithm: $this->id(),
            ciphertext: $aesEnvelope->ciphertext,
            iv: $aesEnvelope->iv,
            tag: $aesEnvelope->tag,
            meta: [
                'encrypted_key' => $encryptedKey->toJson(),
                'key_algorithm' => $this->rsa->id(),
                'message_algorithm' => $this->aes->id(),
            ],
        );
    }

    public function decrypt(CryptoEnvelope $envelope, #[SensitiveParameter] string $privateKey, ?string $passphrase = null): string
    {
        if ($envelope->algorithm !== $this->id()) {
            throw CryptoException::invalidAlgorithm($envelope->algorithm);
        }

        $encryptedKey = $envelope->meta['encrypted_key'] ?? null;

        if (! is_string($encryptedKey)) {
            throw CryptoException::invalidPayload($this->id());
        }

        $symmetricKey = $this->rsa->decrypt(
            CryptoEnvelope::fromJson($encryptedKey),
            $privateKey,
            $passphrase,
        );

        $aesEnvelope = new CryptoEnvelope(
            algorithm: $this->aes->id(),
            ciphertext: $envelope->ciphertext,
            iv: $envelope->iv,
            tag: $envelope->tag,
        );

        return $this->aes->decrypt($aesEnvelope, $symmetricKey);
    }

    public function maxPlaintextBytes(): int
    {
        return 1_048_576;
    }

    public function metadata(): array
    {
        return [
            'rsa_bits' => 2048,
            'message_algorithm' => 'AES-256-GCM',
            'max_plaintext_bytes' => $this->maxPlaintextBytes(),
            'hybrid' => true,
        ];
    }
}
