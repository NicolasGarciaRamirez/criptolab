<?php

declare(strict_types=1);

namespace App\Services\Crypto;

use App\Exceptions\CryptoException;
use SensitiveParameter;

final class AesModesComparisonService
{
    /**
     * @return array<string, mixed>
     */
    public function compare(#[SensitiveParameter] string $plaintext): array
    {
        $key = random_bytes(32);
        $ivCbc = random_bytes(16);
        $ivCtr = random_bytes(16);
        $ivGcm = random_bytes(12);
        $tag = '';

        $ecb = openssl_encrypt($plaintext, 'aes-256-ecb', $key, OPENSSL_RAW_DATA);

        if ($ecb === false) {
            throw new CryptoException('No se pudo cifrar con AES-ECB.');
        }

        $cbc = openssl_encrypt($plaintext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $ivCbc);
        $ctr = openssl_encrypt($plaintext, 'aes-256-ctr', $key, OPENSSL_RAW_DATA, $ivCtr);
        $gcm = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $ivGcm, $tag, '', 16);

        if ($cbc === false || $ctr === false || $gcm === false) {
            throw new CryptoException('No se pudo ejecutar la comparación de modos AES.');
        }

        return [
            'key_hex' => bin2hex($key),
            'plaintext_hex' => bin2hex($plaintext),
            'plaintext_bytes' => strlen($plaintext),
            'modes' => [
                [
                    'id' => 'aes-256-ecb',
                    'label' => 'AES-256-ECB',
                    'iv_hex' => null,
                    'tag_hex' => null,
                    'ciphertext_hex' => bin2hex($ecb),
                    'repeated_blocks' => $this->repeatedBlocks($ecb),
                    'authenticated' => false,
                    'warning' => 'ECB revela patrones: nunca debe usarse para datos reales.',
                ],
                [
                    'id' => 'aes-256-cbc',
                    'label' => 'AES-256-CBC',
                    'iv_hex' => bin2hex($ivCbc),
                    'tag_hex' => null,
                    'ciphertext_hex' => bin2hex($cbc),
                    'repeated_blocks' => $this->repeatedBlocks($cbc),
                    'authenticated' => false,
                    'warning' => 'CBC oculta patrones, pero no autentica el mensaje.',
                ],
                [
                    'id' => 'aes-256-ctr',
                    'label' => 'AES-256-CTR',
                    'iv_hex' => bin2hex($ivCtr),
                    'tag_hex' => null,
                    'ciphertext_hex' => bin2hex($ctr),
                    'repeated_blocks' => $this->repeatedBlocks($ctr),
                    'authenticated' => false,
                    'warning' => 'CTR convierte AES en cifrado de flujo; no autentica.',
                ],
                [
                    'id' => 'aes-256-gcm',
                    'label' => 'AES-256-GCM',
                    'iv_hex' => bin2hex($ivGcm),
                    'tag_hex' => bin2hex($tag),
                    'ciphertext_hex' => bin2hex($gcm),
                    'repeated_blocks' => $this->repeatedBlocks($gcm),
                    'authenticated' => true,
                    'warning' => 'GCM es el modo recomendado: cifra y autentica.',
                ],
            ],
        ];
    }

    private function repeatedBlocks(string $raw): int
    {
        $blocks = str_split($raw, 16);
        $seen = [];
        $repeated = 0;

        foreach ($blocks as $block) {
            if (strlen($block) < 16) {
                continue;
            }

            $key = bin2hex($block);
            $repeated += $seen[$key] ?? 0;
            $seen[$key] = ($seen[$key] ?? 0) + 1;
        }

        return $repeated;
    }
}
