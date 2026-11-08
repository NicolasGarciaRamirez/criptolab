<?php

declare(strict_types=1);

namespace App\Http\Requests\Crypto;

use App\Services\Crypto\Registries\SymmetricCipherRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SymmetricEncryptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'algorithm' => ['required', 'string', Rule::in(array_keys(app(SymmetricCipherRegistry::class)->all()))],
            'plaintext' => ['required', 'string', 'max:100000'],
            'key' => ['required', 'string', 'max:4096'],
        ];
    }
}
