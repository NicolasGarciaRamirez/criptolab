<?php

declare(strict_types=1);

namespace App\Services\Crypto;

use Illuminate\Support\Str;
use SensitiveParameter;

final class BlindIndexService
{
    private string $key;

    public function __construct()
    {
        $this->key = MasterKeyService::decodeKey(
            config('crypto.blind_index_key'),
            'CRYPTO_BLIND_INDEX_KEY',
        );
    }

    /**
     * Genera un HMAC-SHA256 determinista para buscar por igualdad sin
     * almacenar el valor original en claro.
     */
    public function hash(#[SensitiveParameter] string $value): string
    {
        return hash_hmac('sha256', Str::lower(trim($value)), $this->key);
    }

    public static function generateKey(): string
    {
        return 'base64:'.base64_encode(random_bytes(32));
    }
}
