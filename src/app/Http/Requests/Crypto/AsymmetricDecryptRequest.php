<?php

declare(strict_types=1);

namespace App\Http\Requests\Crypto;

use App\Services\Crypto\Registries\AsymmetricCipherRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AsymmetricDecryptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'algorithm' => ['required', 'string', Rule::in(array_keys(app(AsymmetricCipherRegistry::class)->all()))],
            'payload' => ['required', 'string', 'json', 'max:500000'],
            'private_key' => ['required', 'string', 'max:20000'],
            'passphrase' => ['nullable', 'string', 'max:255'],
        ];
    }
}
