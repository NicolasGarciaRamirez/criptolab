<?php

declare(strict_types=1);

namespace App\Services\Crypto;

use App\Exceptions\CryptoException;
use SensitiveParameter;

final class NonceReuseDemoService
{
    /**
     * Demostración exclusivamente educativa: usa el mismo nonce dos veces.
     * Nunca debe usarse fuera de esta pantalla.
     *
     * @return array<string, mixed>
     */
    public function demonstrate(
        string $algorithm,
        #[SensitiveParameter] string $first,
        #[SensitiveParameter] string $second,
    ): array {
        if ($algorithm === 'aes-256-gcm') {
            return $this->gcm($first, $second);
        }

        if ($algorithm === 'chacha20-poly1305') {
            return $this->chacha($first, $second);
        }

        throw CryptoException::invalidAlgorithm($algorithm);
    }

    /**
     * @return array<string, mixed>
     */
    private function gcm(#[SensitiveParameter] string $first, #[SensitiveParameter] string $second): array
    {
        $this->equalLength($first, $second);

        $key = random_bytes(32);
        $nonce = random_bytes(12);
        $tagFirst = '';
        $tagSecond = '';

        $firstCiphertext = openssl_encrypt($first, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tagFirst, '', 16);
        $secondCiphertext = openssl_encrypt($second, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tagSecond, '', 16);

        if ($firstCiphertext === false || $secondCiphertext === false) {
            throw new CryptoException('No se pudo ejecutar la demostración de nonce reutilizado.');
        }

        return [
            'algorithm' => 'aes-256-gcm',
            'key_hex' => bin2hex($key),
            'nonce_hex' => bin2hex($nonce),
            'first' => ['ciphertext_hex' => bin2hex($firstCiphertext), 'tag_hex' => bin2hex($tagFirst)],
            'second' => ['ciphertext_hex' => bin2hex($secondCiphertext), 'tag_hex' => bin2hex($tagSecond)],
            'xor_ciphertexts_hex' => bin2hex($this->xor($firstCiphertext, $secondCiphertext)),
            'xor_plaintexts_hex' => bin2hex($this->xor($first, $second)),
            'leak_matches' => hash_equals(bin2hex($this->xor($firstCiphertext, $secondCiphertext)), bin2hex($this->xor($first, $second))),
            'warning' => 'Con el mismo nonce, la relación XOR de los ciphertexts revela la relación XOR de los textos claros.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function chacha(#[SensitiveParameter] string $first, #[SensitiveParameter] string $second): array
    {
        $this->equalLength($first, $second);

        $key = random_bytes(SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_KEYBYTES);
        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_NPUBBYTES);

        $firstSealed = sodium_crypto_aead_chacha20poly1305_ietf_encrypt($first, '', $nonce, $key);
        $secondSealed = sodium_crypto_aead_chacha20poly1305_ietf_encrypt($second, '', $nonce, $key);

        $tagBytes = SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_ABYTES;
        $firstCiphertext = substr($firstSealed, 0, -$tagBytes);
        $secondCiphertext = substr($secondSealed, 0, -$tagBytes);

        return [
            'algorithm' => 'chacha20-poly1305',
            'key_hex' => bin2hex($key),
            'nonce_hex' => bin2hex($nonce),
            'first' => [
                'ciphertext_hex' => bin2hex($firstCiphertext),
                'tag_hex' => bin2hex(substr($firstSealed, -$tagBytes)),
            ],
            'second' => [
                'ciphertext_hex' => bin2hex($secondCiphertext),
                'tag_hex' => bin2hex(substr($secondSealed, -$tagBytes)),
            ],
            'xor_ciphertexts_hex' => bin2hex($this->xor($firstCiphertext, $secondCiphertext)),
            'xor_plaintexts_hex' => bin2hex($this->xor($first, $second)),
            'leak_matches' => hash_equals(bin2hex($this->xor($firstCiphertext, $secondCiphertext)), bin2hex($this->xor($first, $second))),
            'warning' => 'Reutilizar el nonce en ChaCha20-Poly1305 filtra la relación entre los textos claros.',
        ];
    }

    private function equalLength(string $first, string $second): void
    {
        if ($first === '' || $second === '' || strlen($first) !== strlen($second)) {
            throw new CryptoException('Para esta demostración los dos textos deben tener la misma longitud.');
        }
    }

    private function xor(string $first, string $second): string
    {
        $result = '';

        for ($i = 0; $i < strlen($first); $i++) {
            $result .= $first[$i] ^ $second[$i];
        }

        return $result;
    }
}
