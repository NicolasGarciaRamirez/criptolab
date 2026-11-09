<?php

declare(strict_types=1);

namespace App\Services\Crypto;

use App\Exceptions\CryptoException;
use App\Services\Crypto\Dto\CryptoEnvelope;
use App\Services\Crypto\Registries\AsymmetricCipherRegistry;
use App\Services\Crypto\Registries\SignerRegistry;
use JsonException;
use SensitiveParameter;

final class SignedEncryptionService
{
    public function __construct(
        private readonly SignerRegistry $signers,
        private readonly AsymmetricCipherRegistry $ciphers,
    ) {}

    /**
     * @return array{signing: array<string, mixed>, encryption: array<string, mixed>}
     */
    public function generatePairs(
        string $signerAlgorithm = 'ed25519',
        string $cipherAlgorithm = 'hybrid-rsa-aes-256-gcm',
    ): array {
        $signer = $this->signers->get($signerAlgorithm);
        $cipher = $this->ciphers->get($cipherAlgorithm);
        $signingPair = $signer->generateKeyPair();
        $encryptionPair = $cipher->generateKeyPair();

        return [
            'signing' => [
                'algorithm' => $signer->id(),
                'label' => $signer->label(),
                'public_key' => $signingPair->publicKey,
                'private_key' => $signingPair->privateKey,
                'fingerprint' => hash('sha256', $signingPair->publicKey),
            ],
            'encryption' => [
                'algorithm' => $cipher->id(),
                'label' => $cipher->label(),
                'public_key' => $encryptionPair->publicKey,
                'private_key' => $encryptionPair->privateKey,
                'fingerprint' => hash('sha256', $encryptionPair->publicKey),
            ],
        ];
    }

    public function signAndEncrypt(
        #[SensitiveParameter] string $message,
        string $signerAlgorithm,
        #[SensitiveParameter] string $signingPrivateKey,
        string $signingPublicKey,
        string $cipherAlgorithm,
        string $encryptionPublicKey,
        ?string $passphrase = null,
    ): CryptoEnvelope {
        $signer = $this->signers->get($signerAlgorithm);
        $cipher = $this->ciphers->get($cipherAlgorithm);
        $signature = $signer->sign($message, $signingPrivateKey, $passphrase);

        try {
            $package = json_encode([
                'message' => $message,
                'signature' => $signature,
                'signer_algorithm' => $signer->id(),
                'signer_public_key' => $signingPublicKey,
            ], JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new CryptoException('No se pudo preparar el paquete firmado.');
        }

        $envelope = $cipher->encrypt($package, $encryptionPublicKey);

        return new CryptoEnvelope(
            algorithm: $envelope->algorithm,
            ciphertext: $envelope->ciphertext,
            iv: $envelope->iv,
            tag: $envelope->tag,
            meta: [...$envelope->meta, 'signer_algorithm' => $signer->id()],
            version: $envelope->version,
        );
    }

    /**
     * @return array{message: string, signature_valid: bool, signer_algorithm: string, signature: string}
     */
    public function decryptAndVerify(
        CryptoEnvelope $envelope,
        string $cipherAlgorithm,
        #[SensitiveParameter] string $encryptionPrivateKey,
        ?string $passphrase = null,
        ?string $expectedSignerPublicKey = null,
    ): array {
        $cipher = $this->ciphers->get($cipherAlgorithm);
        $plaintext = $cipher->decrypt($envelope, $encryptionPrivateKey, $passphrase);

        try {
            $package = json_decode($plaintext, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new CryptoException('El paquete descifrado no es JSON válido.');
        }

        if (! is_array($package) || ! is_string($package['message'] ?? null) || ! is_string($package['signature'] ?? null) || ! is_string($package['signer_algorithm'] ?? null)) {
            throw new CryptoException('El paquete descifrado no contiene mensaje y firma.');
        }

        $signer = $this->signers->get($package['signer_algorithm']);
        $publicKey = $expectedSignerPublicKey ?? ($package['signer_public_key'] ?? null);

        if (! is_string($publicKey)) {
            throw new CryptoException('El paquete no contiene la llave pública del firmante.');
        }

        $valid = $signer->verify($package['message'], $package['signature'], $publicKey);

        return [
            'message' => $package['message'],
            'signature_valid' => $valid,
            'signer_algorithm' => $signer->id(),
            'signature' => $package['signature'],
        ];
    }
}
