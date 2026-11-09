<?php

declare(strict_types=1);

namespace App\Http\Requests\Crypto;

use Illuminate\Foundation\Http\FormRequest;

class AadEncryptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'plaintext' => ['required', 'string', 'max:50000'],
            'key' => ['required', 'string', 'max:4096'],
            'aad' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
