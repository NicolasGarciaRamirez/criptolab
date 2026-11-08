<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\User;
use App\Services\Crypto\BlindIndexService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class UniqueEmailHash implements ValidationRule
{
    public function __construct(private readonly ?int $ignoreUserId = null)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        $hash = app(BlindIndexService::class)->hash($value);

        $query = User::query()->where('email_hash', $hash);

        if ($this->ignoreUserId !== null) {
            $query->whereKeyNot($this->ignoreUserId);
        }

        if ($query->exists()) {
            $fail('El correo electrónico ya está registrado.');
        }
    }
}
