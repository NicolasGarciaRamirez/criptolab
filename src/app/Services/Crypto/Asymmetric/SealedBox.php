<?php

declare(strict_types=1);

namespace App\Services\Crypto\Asymmetric;

use App\Exceptions\CryptoException;
use App\Services\Crypto\Contracts\AsymmetricCipher;
use App\Services\Crypto\Dto\AsymmetricKeyPair;
use App\Services\Crypto\Dto\CryptoEnvelope;
use App\Services\Crypto\KeyCodec;
use SensitiveParameter;

final class SealedBox implements AsymmetricCipher
{
    public function id(): string
    {
        return 'sealed-box';
    }

    public function label(): string
    {
        return 'Sodium sealed box (X25519)';
    }

    public function description(): string
    {
        return 'Cifrado asimétrico moderno de Sodium: el remitente solo necesita la llave pública. Sin firmas ni gestión de nonce.';
    }

    public function generateKeyPair(array $options = []): AsymmetricKeyPair
    {
        $keypair = sodium_crypto_box_keypair();
        $public = sodium_crypto_box_publickey($keypair);
        $secret = sodium_crypto_box_secretkey($keypair);

        return new AsymmetricKeyPair(
            algorithm: $this->id(),
            publicKey: base64_encode($public),
            // La llave privada es el keypair completo (secret || public), como exige sealed_box_open.
            privateKey: base64_encode($secret.$public),
            meta: ['encoding' => 'base64', 'curve' => 'X25519'],
        );
    }

    public function encrypt(string $plaintext, string $publicKey): CryptoEnvelope
    {
        $public = KeyCodec::decode($publicKey, SODIUM_CRYPTO_BOX_PUBLICKEYBYTES, 'La llave pública');

        $sealed = sodium_crypto_box_seal($plaintext, $public);

        return new CryptoEnvelope(
            algorithm: $this->id(),
            ciphertext: base64_encode($sealed),
            meta: ['curve' => 'X25519', 'encoding' => 'base64'],
        );
    }

    public function decrypt(CryptoEnvelope $envelope, #[SensitiveParameter] string $privateKey, ?string $passphrase = null): string
    {
        if ($envelope->algorithm !== $this->id()) {
            throw CryptoException::invalidAlgorithm($envelope->algorithm);
        }

        $keypair = KeyCodec::decode($privateKey, SODIUM_CRYPTO_BOX_KEYPAIRBYTES, 'La llave privada');
        $ciphertext = base64_decode($envelope->ciphertext, true);

        if ($ciphertext === false) {
            throw CryptoException::invalidPayload($this->id());
        }

        $plaintext = sodium_crypto_box_seal_open($ciphertext, $keypair);

        if ($plaintext === false) {
            throw CryptoException::decryptionFailed($this->id());
        }

        return $plaintext;
    }

    public function maxPlaintextBytes(): int
    {
        return 1_048_576;
    }

    public function metadata(): array
    {
        return [
            'public_key_bytes' => SODIUM_CRYPTO_BOX_PUBLICKEYBYTES,
            'private_key_bytes' => SODIUM_CRYPTO_BOX_KEYPAIRBYTES,
            'curve' => 'X25519',
            'max_plaintext_bytes' => $this->maxPlaintextBytes(),
        ];
    }
}
