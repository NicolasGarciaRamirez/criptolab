<?php

declare(strict_types=1);

namespace App\Services\Crypto\Asymmetric;

use App\Exceptions\CryptoException;
use App\Services\Crypto\Contracts\Signer;
use App\Services\Crypto\Dto\AsymmetricKeyPair;
use App\Services\Crypto\KeyCodec;
use SensitiveParameter;

final class Ed25519Signer implements Signer
{
    public function id(): string
    {
        return 'ed25519';
    }

    public function label(): string
    {
        return 'Ed25519';
    }

    public function description(): string
    {
        return 'Firmas digitales modernas y rápidas con llaves pequeñas. Verifica si el mensaje fue alterado.';
    }

    public function generateKeyPair(): AsymmetricKeyPair
    {
        $keypair = sodium_crypto_sign_keypair();
        $public = sodium_crypto_sign_publickey($keypair);

        return new AsymmetricKeyPair(
            algorithm: $this->id(),
            publicKey: base64_encode($public),
            // 64 bytes: secret key || public key.
            privateKey: base64_encode($keypair),
            meta: ['encoding' => 'base64', 'signature_bytes' => SODIUM_CRYPTO_SIGN_BYTES],
        );
    }

    public function sign(string $message, #[SensitiveParameter] string $privateKey, ?string $passphrase = null): string
    {
        $keypair = KeyCodec::decode($privateKey, SODIUM_CRYPTO_SIGN_KEYPAIRBYTES, 'La llave privada');
        $secret = sodium_crypto_sign_secretkey($keypair);

        return base64_encode(sodium_crypto_sign_detached($message, $secret));
    }

    public function verify(string $message, string $signature, string $publicKey): bool
    {
        try {
            $public = KeyCodec::decode($publicKey, SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES, 'La llave pública');
            $rawSignature = base64_decode($signature, true);

            if ($rawSignature === false || strlen($rawSignature) !== SODIUM_CRYPTO_SIGN_BYTES) {
                return false;
            }

            return sodium_crypto_sign_verify_detached($rawSignature, $message, $public);
        } catch (CryptoException) {
            return false;
        }
    }

    public function metadata(): array
    {
        return [
            'public_key_bytes' => SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES,
            'private_key_bytes' => SODIUM_CRYPTO_SIGN_KEYPAIRBYTES,
            'signature_bytes' => SODIUM_CRYPTO_SIGN_BYTES,
        ];
    }
}
