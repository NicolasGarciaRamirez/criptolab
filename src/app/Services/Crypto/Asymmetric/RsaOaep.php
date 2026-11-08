<?php

declare(strict_types=1);

namespace App\Services\Crypto\Asymmetric;

use App\Exceptions\CryptoException;
use App\Services\Crypto\Contracts\AsymmetricCipher;
use App\Services\Crypto\Dto\AsymmetricKeyPair;
use App\Services\Crypto\Dto\CryptoEnvelope;
use SensitiveParameter;

final class RsaOaep implements AsymmetricCipher
{
    public function __construct(private readonly int $bits = 2048)
    {
        if (! in_array($bits, [2048, 4096], true)) {
            throw new CryptoException('RSA solo está configurado para 2048 o 4096 bits.');
        }
    }

    public function id(): string
    {
        return 'rsa-oaep-'.$this->bits;
    }

    public function label(): string
    {
        return 'RSA-OAEP '.$this->bits;
    }

    public function description(): string
    {
        return 'Cifrado asimétrico con relleno OAEP. Ideal para mensajes cortos como una contraseña o una llave AES.';
    }

    public function generateKeyPair(array $options = []): AsymmetricKeyPair
    {
        $resource = openssl_pkey_new([
            'private_key_bits' => $this->bits,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        if ($resource === false) {
            throw new CryptoException('No se pudo generar el par RSA: '.openssl_error_string());
        }

        $passphrase = $options['passphrase'] ?? null;
        $privateKey = '';

        if (! openssl_pkey_export($resource, $privateKey, $passphrase)) {
            throw new CryptoException('No se pudo exportar la llave privada RSA.');
        }

        $details = openssl_pkey_get_details($resource);

        if ($details === false) {
            throw new CryptoException('No se pudieron leer los detalles del par RSA.');
        }

        return new AsymmetricKeyPair(
            algorithm: $this->id(),
            publicKey: $details['key'],
            privateKey: $privateKey,
            meta: [
                'bits' => $details['bits'],
                'encoding' => 'pem',
                'padding' => 'OAEP',
                'passphrase_protected' => filled($passphrase),
            ],
        );
    }

    public function encrypt(string $plaintext, string $publicKey): CryptoEnvelope
    {
        $key = openssl_pkey_get_public($publicKey);

        if ($key === false) {
            throw CryptoException::invalidKey($this->id());
        }

        $details = openssl_pkey_get_details($key);
        $bits = is_array($details) ? (int) $details['bits'] : $this->bits;
        $max = intdiv($bits, 8) - 42;

        if (strlen($plaintext) > $max) {
            throw CryptoException::messageTooLong($this->id(), $max);
        }

        if (! openssl_public_encrypt($plaintext, $encrypted, $key, OPENSSL_PKCS1_OAEP_PADDING)) {
            throw new CryptoException('No se pudo cifrar con RSA-OAEP: '.openssl_error_string());
        }

        return new CryptoEnvelope(
            algorithm: $this->id(),
            ciphertext: base64_encode($encrypted),
            meta: ['bits' => $bits, 'padding' => 'OAEP'],
        );
    }

    public function decrypt(CryptoEnvelope $envelope, #[SensitiveParameter] string $privateKey, ?string $passphrase = null): string
    {
        if ($envelope->algorithm !== $this->id()) {
            throw CryptoException::invalidAlgorithm($envelope->algorithm);
        }

        if (str_contains($privateKey, 'ENCRYPTED PRIVATE KEY') && blank($passphrase)) {
            throw CryptoException::invalidKey($this->id());
        }

        $ciphertext = base64_decode($envelope->ciphertext, true);
        $key = openssl_pkey_get_private($privateKey, $passphrase);

        if ($ciphertext === false || $key === false) {
            throw CryptoException::invalidKey($this->id());
        }

        if (! openssl_private_decrypt($ciphertext, $decrypted, $key, OPENSSL_PKCS1_OAEP_PADDING)) {
            throw CryptoException::decryptionFailed($this->id());
        }

        return $decrypted;
    }

    public function maxPlaintextBytes(): int
    {
        return intdiv($this->bits, 8) - 42;
    }

    public function metadata(): array
    {
        return [
            'bits' => $this->bits,
            'max_plaintext_bytes' => $this->maxPlaintextBytes(),
            'padding' => 'OAEP (SHA-1 por defecto en OpenSSL/PHP)',
            'authenticated' => true,
        ];
    }
}
