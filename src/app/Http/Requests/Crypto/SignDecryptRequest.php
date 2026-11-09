<?php

declare(strict_types=1);

namespace App\Http\Requests\Crypto;

use App\Services\Crypto\Registries\AsymmetricCipherRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SignDecryptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'payload' => ['required', 'string', 'json', 'max:500000'],
            'cipher_algorithm' => ['required', Rule::in(array_keys(app(AsymmetricCipherRegistry::class)->all()))],
            'encryption_private_key' => ['required', 'string', 'max:20000'],
            'passphrase' => ['nullable', 'string', 'max:255'],
            'expected_signer_public_key' => ['nullable', 'string', 'max:20000'],
        ];
    }
}
