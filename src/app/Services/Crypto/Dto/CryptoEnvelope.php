<?php

declare(strict_types=1);

namespace App\Services\Crypto\Dto;

use App\Exceptions\CryptoException;
use JsonException;

/**
 * Sobre de texto cifrado versionado. Todos los valores binarios viajan en
 * Base64 y el algoritmo queda identificado dentro del propio sobre.
 */
final readonly class CryptoEnvelope
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public string $algorithm,
        public string $ciphertext,
        public ?string $iv = null,
        public ?string $tag = null,
        public array $meta = [],
        public int $version = 1,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'version' => $this->version,
            'algorithm' => $this->algorithm,
            'ciphertext' => $this->ciphertext,
            'iv' => $this->iv,
            'tag' => $this->tag,
            'meta' => $this->meta,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['algorithm'], $data['ciphertext']) || ! is_string($data['algorithm']) || ! is_string($data['ciphertext'])) {
            throw new CryptoException('El sobre cifrado no contiene algoritmo y ciphertext.');
        }

        return new self(
            algorithm: $data['algorithm'],
            ciphertext: $data['ciphertext'],
            iv: isset($data['iv']) && is_string($data['iv']) ? $data['iv'] : null,
            tag: isset($data['tag']) && is_string($data['tag']) ? $data['tag'] : null,
            meta: isset($data['meta']) && is_array($data['meta']) ? $data['meta'] : [],
            version: isset($data['version']) && is_int($data['version']) ? $data['version'] : 1,
        );
    }

    public static function fromJson(string $json): self
    {
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new CryptoException('El sobre cifrado no es un JSON válido.');
        }

        if (! is_array($data)) {
            throw new CryptoException('El sobre cifrado no es un objeto JSON válido.');
        }

        return self::fromArray($data);
    }

    /**
     * Representación compacta: iv || tag || ciphertext (bytes) en Base64.
     */
    public function combined(): string
    {
        $raw = '';

        if ($this->iv !== null) {
            $raw .= base64_decode($this->iv, true) ?: '';
        }

        if ($this->tag !== null) {
            $raw .= base64_decode($this->tag, true) ?: '';
        }

        $raw .= base64_decode($this->ciphertext, true) ?: '';

        return base64_encode($raw);
    }

    /**
     * @return array<string, int>
     */
    public function sizes(): array
    {
        return [
            'ciphertext' => strlen(base64_decode($this->ciphertext, true) ?: ''),
            'iv' => $this->iv !== null ? strlen(base64_decode($this->iv, true) ?: '') : 0,
            'tag' => $this->tag !== null ? strlen(base64_decode($this->tag, true) ?: '') : 0,
        ];
    }
}
