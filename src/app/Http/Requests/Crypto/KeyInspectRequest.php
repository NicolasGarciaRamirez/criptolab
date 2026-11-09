<?php

declare(strict_types=1);

namespace App\Http\Requests\Crypto;

use App\Services\Crypto\Registries\AsymmetricCipherRegistry;
use App\Services\Crypto\Registries\SignerRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KeyInspectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'public_key' => ['required', 'string', 'max:20000'],
            'private_key' => ['nullable', 'string', 'max:20000'],
            'algorithm' => ['required', Rule::in([...array_keys(app(AsymmetricCipherRegistry::class)->all()), ...array_keys(app(SignerRegistry::class)->all())])],
            'name' => ['nullable', 'string', 'max:100'],
            'save' => ['sometimes', 'boolean'],
        ];
    }
}
