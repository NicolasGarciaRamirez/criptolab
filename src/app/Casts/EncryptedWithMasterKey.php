<?php

declare(strict_types=1);

namespace App\Casts;

use App\Services\Crypto\MasterKeyService;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use SensitiveParameter;

/**
 * @implements CastsAttributes<string|null, string|null>
 */
final class EncryptedWithMasterKey implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value === null ? null : app(MasterKeyService::class)->decrypt($value);
    }

    public function set(Model $model, string $key, #[SensitiveParameter] mixed $value, array $attributes): ?string
    {
        return $value === null ? null : app(MasterKeyService::class)->encrypt((string) $value);
    }
}
