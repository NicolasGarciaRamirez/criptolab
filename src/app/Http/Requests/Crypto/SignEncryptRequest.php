<?php

declare(strict_types=1);

namespace App\Http\Requests\Crypto;

use App\Services\Crypto\Registries\AsymmetricCipherRegistry;
use App\Services\Crypto\Registries\SignerRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SignEncryptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:10000'],
            'signer_algorithm' => ['required', Rule::in(array_keys(app(SignerRegistry::class)->all()))],
            'cipher_algorithm' => ['required', Rule::in(array_keys(app(AsymmetricCipherRegistry::class)->all()))],
            'signing_private_key' => ['required', 'string', 'max:20000'],
            'signing_public_key' => ['required', 'string', 'max:20000'],
            'encryption_public_key' => ['required', 'string', 'max:20000'],
            'passphrase' => ['nullable', 'string', 'max:255'],
        ];
    }
}
