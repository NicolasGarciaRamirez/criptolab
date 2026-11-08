<?php

declare(strict_types=1);

namespace App\Http\Requests\Crypto;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeriveKeyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'method' => ['required', Rule::in(['pbkdf2', 'argon2id'])],
            'passphrase' => ['required', 'string', 'max:1000'],
            'salt' => ['nullable', 'string', 'max:256'],
            'iterations' => ['nullable', 'integer', 'min:1000', 'max:1000000'],
            'bytes' => ['required', 'integer', Rule::in([16, 32])],
        ];
    }
}
