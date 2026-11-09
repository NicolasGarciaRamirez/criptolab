<?php

declare(strict_types=1);

namespace App\Http\Requests\Crypto;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NonceReuseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'algorithm' => ['required', Rule::in(['aes-256-gcm', 'chacha20-poly1305'])],
            'first' => ['required', 'string', 'max:10000'],
            'second' => ['required', 'string', 'max:10000'],
        ];
    }
}
