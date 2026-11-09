<?php

declare(strict_types=1);

namespace App\Services\Crypto\Asymmetric;

use App\Exceptions\CryptoException;
use App\Services\Crypto\Contracts\Signer;
use App\Services\Crypto\Dto\AsymmetricKeyPair;
use SensitiveParameter;

final class EcdsaSigner implements Signer
{
    public function __construct(
        private readonly string $curve = 'prime256v1',
        private readonly string $id = 'ecdsa-p256',
    ) {
        if (! in_array($curve, ['prime256v1', 'secp384r1'], true)) {
            throw new CryptoException('Curva ECDSA no soportada.');
        }
    }

    public function id(): string
    {
        return $this->id;
    }

    public function label(): string
    {
        return $this->curve === 'secp384r1' ? 'ECDSA P-384 (SHA-384)' : 'ECDSA P-256 (SHA-256)';
    }

    public function description(): string
    {
        return 'Firma digital con curvas elípticas. Llaves y firmas mucho más pequeñas que RSA.';
    }

    public function generateKeyPair(): AsymmetricKeyPair
    {
        $resource = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => $this->curve,
        ]);

        if ($resource === false) {
            throw new CryptoException('No se pudo generar la llave ECDSA.');
        }

        $privateKey = '';

        if (! openssl_pkey_export($resource, $privateKey)) {
            throw new CryptoException('No se pudo exportar la llave privada ECDSA.');
        }

        $details = openssl_pkey_get_details($resource);

        if ($details === false) {
            throw new CryptoException('No se pudieron leer los detalles de la llave ECDSA.');
        }

        return new AsymmetricKeyPair(
            algorithm: $this->id,
            publicKey: $details['key'],
            privateKey: $privateKey,
            meta: [
                'encoding' => 'pem',
                'curve' => $this->curve,
                'bits' => $details['bits'],
                'hash' => $this->opensslAlgorithm() === OPENSSL_ALGO_SHA384 ? 'sha384' : 'sha256',
            ],
        );
    }

    public function sign(string $message, #[SensitiveParameter] string $privateKey, ?string $passphrase = null): string
    {
        if (str_contains($privateKey, 'ENCRYPTED PRIVATE KEY') && blank($passphrase)) {
            throw CryptoException::invalidKey($this->id);
        }

        $key = openssl_pkey_get_private($privateKey, $passphrase);

        if ($key === false) {
            throw CryptoException::invalidKey($this->id);
        }

        $signature = '';

        if (! openssl_sign($message, $signature, $key, $this->opensslAlgorithm())) {
            throw new CryptoException('No se pudo firmar con ECDSA.');
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

        return openssl_verify($message, $rawSignature, $key, $this->opensslAlgorithm()) === 1;
    }

    public function metadata(): array
    {
        return [
            'curve' => $this->curve,
            'bits' => $this->curve === 'secp384r1' ? 384 : 256,
            'hash' => $this->curve === 'secp384r1' ? 'SHA-384' : 'SHA-256',
            'encoding' => 'base64',
        ];
    }

    private function opensslAlgorithm(): int
    {
        return $this->curve === 'secp384r1' ? OPENSSL_ALGO_SHA384 : OPENSSL_ALGO_SHA256;
    }
}
