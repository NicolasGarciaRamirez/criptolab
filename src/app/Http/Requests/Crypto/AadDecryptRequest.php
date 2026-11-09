<?php

declare(strict_types=1);

namespace App\Http\Requests\Crypto;

use Illuminate\Foundation\Http\FormRequest;

class AadDecryptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'payload' => ['required', 'string', 'json', 'max:500000'],
            'key' => ['required', 'string', 'max:4096'],
            'aad' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
