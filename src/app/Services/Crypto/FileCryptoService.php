<?php

declare(strict_types=1);

namespace App\Services\Crypto;

use App\Exceptions\CryptoException;
use App\Services\Crypto\Contracts\SymmetricCipher;
use App\Services\Crypto\Dto\CryptoEnvelope;
use App\Services\Crypto\Registries\SymmetricCipherRegistry;
use Illuminate\Http\UploadedFile;
use JsonException;
use SensitiveParameter;

final class FileCryptoService
{
    public function __construct(private readonly SymmetricCipherRegistry $ciphers) {}

    /**
     * Cifra un archivo en memoria. El nombre y el MIME viajan dentro del
     * payload cifrado, no en metadatos visibles.
     *
     * @return array{envelope: CryptoEnvelope, original_name: string, mime: string, original_bytes: int, ciphertext_bytes: int}
     */
    public function encrypt(UploadedFile $file, string $algorithm, #[SensitiveParameter] string $key): array
    {
        $cipher = $this->authenticatedCipher($algorithm);
        $contents = $file->get();

        if ($contents === false) {
            throw new CryptoException('No se pudo leer el archivo subido.');
        }

        $this->assertSize(strlen($contents));

        try {
            $payload = json_encode([
                'name' => $this->safeFilename($file->getClientOriginalName()),
                'mime' => $file->getMimeType() ?: 'application/octet-stream',
                'size' => strlen($contents),
                'data' => base64_encode($contents),
            ], JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new CryptoException('No se pudo preparar el archivo para cifrarlo.');
        }

        $envelope = $cipher->encrypt($payload, $key);

        return [
            'envelope' => $envelope,
            'original_name' => $this->safeFilename($file->getClientOriginalName()),
            'mime' => $file->getMimeType() ?: 'application/octet-stream',
            'original_bytes' => strlen($contents),
            'ciphertext_bytes' => strlen(base64_decode($envelope->ciphertext, true) ?: ''),
        ];
    }

    /**
     * @return array{name: string, mime: string, contents: string, bytes: int}
     */
    public function decrypt(CryptoEnvelope $envelope, string $algorithm, #[SensitiveParameter] string $key): array
    {
        $cipher = $this->authenticatedCipher($algorithm);
        $plaintext = $cipher->decrypt($envelope, $key);

        try {
            $payload = json_decode($plaintext, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new CryptoException('El archivo cifrado no contiene un payload válido.');
        }

        if (! is_array($payload) || ! isset($payload['data'], $payload['name']) || ! is_string($payload['data']) || ! is_string($payload['name'])) {
            throw new CryptoException('El archivo cifrado no contiene nombre y datos.');
        }

        $contents = base64_decode($payload['data'], true);

        if ($contents === false) {
            throw new CryptoException('Los datos del archivo cifrado están corruptos.');
        }

        $this->assertSize(strlen($contents));

        return [
            'name' => $this->safeFilename($payload['name']),
            'mime' => is_string($payload['mime'] ?? null) ? $payload['mime'] : 'application/octet-stream',
            'contents' => $contents,
            'bytes' => strlen($contents),
        ];
    }

    public function maxBytes(): int
    {
        return (int) config('crypto.file_max_bytes', 5 * 1024 * 1024);
    }

    private function authenticatedCipher(string $algorithm): SymmetricCipher
    {
        $cipher = $this->ciphers->get($algorithm);

        if (($cipher->metadata()['authenticated'] ?? false) !== true) {
            throw new CryptoException('Para archivos solo se permiten algoritmos autenticados.');
        }

        return $cipher;
    }

    private function assertSize(int $bytes): void
    {
        if ($bytes > $this->maxBytes()) {
            throw new CryptoException('El archivo supera el máximo permitido de '.(int) ($this->maxBytes() / 1024 / 1024).' MB.');
        }
    }

    private function safeFilename(string $name): string
    {
        $base = basename(str_replace('\\', '/', $name));
        $base = preg_replace('/[^\pL\pN._ -]/u', '_', $base) ?: 'archivo.bin';
        $base = trim($base);

        if ($base === '') {
            return 'archivo.bin';
        }

        return mb_substr($base, 0, 150);
    }
}
