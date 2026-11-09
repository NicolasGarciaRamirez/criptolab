<?php

declare(strict_types=1);

namespace App\Services\Crypto;

use App\Exceptions\CryptoException;
use App\Services\Crypto\Dto\CryptoEnvelope;
use App\Services\Crypto\Symmetric\Aes256Gcm;
use SensitiveParameter;

final class KeyAgreementService
{
    public function __construct(private readonly Aes256Gcm $aes) {}

    /**
     * @return array{public_key: string, private_key: string}
     */
    public function generatePair(): array
    {
        $keypair = sodium_crypto_box_keypair();

        return [
            'public_key' => base64_encode(sodium_crypto_box_publickey($keypair)),
            'private_key' => base64_encode(sodium_crypto_box_secretkey($keypair)),
        ];
    }

    public function sharedSecret(#[SensitiveParameter] string $privateKey, string $peerPublicKey): string
    {
        $private = KeyCodec::decode($privateKey, SODIUM_CRYPTO_BOX_SECRETKEYBYTES, 'La llave privada X25519');
        $public = KeyCodec::decode($peerPublicKey, SODIUM_CRYPTO_BOX_PUBLICKEYBYTES, 'La llave pública X25519');

        try {
            $shared = sodium_crypto_scalarmult($private, $public);
        } catch (\SodiumException) {
            throw new CryptoException('No se pudo calcular el secreto compartido X25519.');
        }

        return base64_encode($shared);
    }

    /**
     * @return array{key: string, key_hex: string, fingerprint: string, bytes: int}
     */
    public function deriveAesKey(#[SensitiveParameter] string $sharedSecret): array
    {
        $raw = KeyCodec::decode($sharedSecret, SODIUM_CRYPTO_SCALARMULT_BYTES, 'El secreto compartido');
        $key = hash_hkdf('sha256', $raw, 32, 'criptolab-x25519-aes-256-gcm');

        return [
            'key' => base64_encode($key),
            'key_hex' => bin2hex($key),
            'fingerprint' => hash('sha256', $key),
            'bytes' => strlen($key),
        ];
    }

    public function encryptMessage(string $message, #[SensitiveParameter] string $sharedSecret): CryptoEnvelope
    {
        $derived = $this->deriveAesKey($sharedSecret);

        return $this->aes->encrypt($message, $derived['key']);
    }

    public function decryptMessage(CryptoEnvelope $envelope, #[SensitiveParameter] string $sharedSecret): string
    {
        $derived = $this->deriveAesKey($sharedSecret);

        return $this->aes->decrypt($envelope, $derived['key']);
    }

    public function fingerprint(#[SensitiveParameter] string $secret): string
    {
        return hash('sha256', KeyCodec::decode($secret, SODIUM_CRYPTO_SCALARMULT_BYTES, 'El secreto compartido'));
    }
}
