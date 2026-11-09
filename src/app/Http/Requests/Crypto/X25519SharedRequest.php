<?php

declare(strict_types=1);

namespace App\Http\Requests\Crypto;

use Illuminate\Foundation\Http\FormRequest;

class X25519SharedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'alice_private_key' => ['required', 'string', 'max:256'],
            'alice_public_key' => ['required', 'string', 'max:256'],
            'bob_private_key' => ['required', 'string', 'max:256'],
            'bob_public_key' => ['required', 'string', 'max:256'],
            'message' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
