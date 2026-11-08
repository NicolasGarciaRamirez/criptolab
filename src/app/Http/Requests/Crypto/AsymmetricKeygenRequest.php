<?php

declare(strict_types=1);

namespace App\Http\Requests\Crypto;

use App\Services\Crypto\Registries\AsymmetricCipherRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AsymmetricKeygenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'algorithm' => ['required', 'string', Rule::in(array_keys(app(AsymmetricCipherRegistry::class)->all()))],
            'name' => ['nullable', 'string', 'max:100'],
            'save' => ['sometimes', 'boolean'],
            'passphrase' => ['nullable', 'string', 'max:255'],
        ];
    }
}
