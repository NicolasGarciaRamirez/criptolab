<?php

declare(strict_types=1);

namespace App\Services\Crypto;

use App\Exceptions\CryptoException;
use SensitiveParameter;

final class X509Service
{
    /**
     * @return array{certificate: string, private_key: string, csr: string, parsed: array<string, mixed>, fingerprint: string, verified: bool}
     */
    public function generateSelfSigned(
        string $commonName,
        string $organization = 'CriptoLab',
        string $country = 'CO',
        int $days = 365,
    ): array {
        $privateKey = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        if ($privateKey === false) {
            throw new CryptoException('No se pudo generar la llave para el certificado.');
        }

        $dn = [
            'countryName' => $country,
            'organizationName' => $organization,
            'commonName' => $commonName,
        ];

        $csr = openssl_csr_new($dn, $privateKey, ['digest_alg' => 'sha256']);

        if ($csr === false) {
            throw new CryptoException('No se pudo crear la solicitud de certificado.');
        }

        $certificate = openssl_csr_sign($csr, null, $privateKey, max(1, min(3650, $days)), ['digest_alg' => 'sha256']);

        if ($certificate === false) {
            throw new CryptoException('No se pudo firmar el certificado autofirmado.');
        }

        $certificatePem = '';
        $privateKeyPem = '';
        $csrPem = '';

        openssl_x509_export($certificate, $certificatePem);
        openssl_pkey_export($privateKey, $privateKeyPem);
        openssl_csr_export($csr, $csrPem);

        $parsed = openssl_x509_parse($certificate);

        if ($parsed === false) {
            throw new CryptoException('No se pudo inspeccionar el certificado generado.');
        }

        $publicKey = openssl_pkey_get_public($certificate);
        $verified = $publicKey !== false && openssl_x509_verify($certificate, $publicKey) === 1;

        return [
            'certificate' => $certificatePem,
            'private_key' => $privateKeyPem,
            'csr' => $csrPem,
            'parsed' => $this->summarize($parsed),
            'fingerprint' => $this->fingerprint($certificate),
            'verified' => $verified,
        ];
    }

    /**
     * @return array{parsed: array<string, mixed>, fingerprint: string, verified: bool}
     */
    public function inspect(#[SensitiveParameter] string $certificatePem): array
    {
        $certificate = @openssl_x509_read($certificatePem);

        if ($certificate === false) {
            throw new CryptoException('El certificado PEM no es válido.');
        }

        $parsed = openssl_x509_parse($certificate);

        if ($parsed === false) {
            throw new CryptoException('No se pudo interpretar el certificado.');
        }

        $publicKey = @openssl_pkey_get_public($certificate);
        $verified = $publicKey !== false && @openssl_x509_verify($certificate, $publicKey) === 1;

        return [
            'parsed' => $this->summarize($parsed),
            'fingerprint' => $this->fingerprint($certificate),
            'verified' => $verified,
        ];
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @return array<string, mixed>
     */
    private function summarize(array $parsed): array
    {
        return [
            'name' => $parsed['name'] ?? null,
            'subject' => $parsed['subject'] ?? [],
            'issuer' => $parsed['issuer'] ?? [],
            'valid_from' => isset($parsed['validFrom_time_t']) ? date('Y-m-d H:i:s', $parsed['validFrom_time_t']) : null,
            'valid_to' => isset($parsed['validTo_time_t']) ? date('Y-m-d H:i:s', $parsed['validTo_time_t']) : null,
            'serial_number' => $parsed['serialNumber'] ?? null,
            'signature_type' => $parsed['signatureTypeLN'] ?? null,
        ];
    }

    private function fingerprint(mixed $certificate): string
    {
        $fingerprint = openssl_x509_fingerprint($certificate, 'sha256');

        return is_string($fingerprint) ? $fingerprint : '';
    }
}
