<?php

declare(strict_types=1);

namespace App\Services\Crypto\Asymmetric;

use App\Exceptions\CryptoException;
use App\Services\Crypto\Contracts\Signer;
use App\Services\Crypto\Dto\AsymmetricKeyPair;
use SensitiveParameter;

final class RsaSigner implements Signer
{
    public function __construct(private readonly int $bits = 2048)
    {
        if (! in_array($bits, [2048, 4096], true)) {
            throw new CryptoException('RSA solo está configurado para 2048 o 4096 bits.');
        }
    }

    public function id(): string
    {
        return 'rsa-sha256-'.$this->bits;
    }

    public function label(): string
    {
        return 'RSA-SHA256 '.$this->bits;
    }

    public function description(): string
    {
        return 'Firma digital RSA con SHA-256. Si el mensaje o la firma cambian, la verificación falla.';
    }

    public function generateKeyPair(): AsymmetricKeyPair
    {
        $resource = openssl_pkey_new([
            'private_key_bits' => $this->bits,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        if ($resource === false) {
            throw new CryptoException('No se pudo generar el par RSA para firmas.');
        }

        $privateKey = '';

        if (! openssl_pkey_export($resource, $privateKey)) {
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
            meta: ['bits' => $details['bits'], 'encoding' => 'pem', 'hash' => 'sha256'],
        );
    }

    public function sign(string $message, #[SensitiveParameter] string $privateKey, ?string $passphrase = null): string
    {
        if (str_contains($privateKey, 'ENCRYPTED PRIVATE KEY') && blank($passphrase)) {
            throw CryptoException::invalidKey($this->id());
        }

        $key = openssl_pkey_get_private($privateKey, $passphrase);

        if ($key === false) {
            throw CryptoException::invalidKey($this->id());
        }

        $signature = '';

        if (! openssl_sign($message, $signature, $key, OPENSSL_ALGO_SHA256)) {
            throw new CryptoException('No se pudo firmar con RSA-SHA256.');
        }

        return base64_encode($signature);
    }

    public function verify(string $message, string $signature, string $publicKey): bool
    {
        $key = openssl_pkey_get_public($publicKey);
        $rawSignature = base64_decode($signature, true);

        if ($key === false || $rawSignature === false) {
            return false;
        }

        return openssl_verify($message, $rawSignature, $key, OPENSSL_ALGO_SHA256) === 1;
    }

    public function metadata(): array
    {
        return [
            'bits' => $this->bits,
            'hash' => 'SHA-256',
            'encoding' => 'base64',
        ];
    }
}
