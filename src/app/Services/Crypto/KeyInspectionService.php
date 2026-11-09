<?php

declare(strict_types=1);

namespace App\Services\Crypto;

use App\Exceptions\CryptoException;
use SensitiveParameter;

final class KeyInspectionService
{
    /**
     * @return array{kind: string, type: string, bits: int|null, curve: string|null, fingerprint: string, public_key: string}
     */
    public function inspect(#[SensitiveParameter] string $pem): array
    {
        if (str_contains($pem, 'ENCRYPTED PRIVATE KEY')) {
            throw new CryptoException('Las llaves privadas con passphrase no se pueden inspeccionar sin la frase de paso. Usa un PEM sin cifrar o genera una nueva llave.');
        }

        $publicKey = @openssl_pkey_get_public($pem);
        $kind = 'public';
        $details = $publicKey !== false ? openssl_pkey_get_details($publicKey) : false;

        if ($details === false) {
            $privateKey = @openssl_pkey_get_private($pem);
            $kind = 'private';
            $details = $privateKey !== false ? openssl_pkey_get_details($privateKey) : false;
        }

        if ($details === false || ! isset($details['key'])) {
            throw new CryptoException('La llave PEM no es válida o no se pudo interpretar.');
        }

        return [
            'kind' => $kind,
            'type' => $this->typeName((int) ($details['type'] ?? -1)),
            'bits' => isset($details['bits']) ? (int) $details['bits'] : null,
            'curve' => isset($details['curve_name']) ? (string) $details['curve_name'] : null,
            'fingerprint' => hash('sha256', $details['key']),
            'public_key' => $details['key'],
        ];
    }

    /**
     * @param  array<string, mixed>  $inspection
     */
    public function assertCompatible(string $algorithm, array $inspection): void
    {
        $type = $inspection['type'] ?? '';

        if (str_starts_with($algorithm, 'rsa-') && $type !== 'RSA') {
            throw new CryptoException('El algoritmo seleccionado requiere una llave RSA.');
        }

        if (str_starts_with($algorithm, 'ecdsa-') && $type !== 'EC') {
            throw new CryptoException('El algoritmo seleccionado requiere una llave EC.');
        }

        if ($type === 'EC' && $algorithm === 'hybrid-rsa-aes-256-gcm') {
            throw new CryptoException('El cifrado híbrido configurado requiere una llave RSA.');
        }

        if (in_array($algorithm, ['sealed-box', 'ed25519'], true)) {
            throw new CryptoException('Ese algoritmo usa llaves Base64 de Sodium, no PEM. Impórtalo desde el laboratorio correspondiente.');
        }
    }

    private function typeName(int $type): string
    {
        return match ($type) {
            OPENSSL_KEYTYPE_RSA => 'RSA',
            OPENSSL_KEYTYPE_EC => 'EC',
            OPENSSL_KEYTYPE_DSA => 'DSA',
            OPENSSL_KEYTYPE_DH => 'DH',
            default => 'Desconocido',
        };
    }
}
