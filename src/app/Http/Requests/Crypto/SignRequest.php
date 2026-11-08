<?php

declare(strict_types=1);

namespace App\Http\Requests\Crypto;

use App\Services\Crypto\Registries\SignerRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'algorithm' => ['required', 'string', Rule::in(array_keys(app(SignerRegistry::class)->all()))],
            'message' => ['required', 'string', 'max:100000'],
            'private_key' => ['required', 'string', 'max:20000'],
            'passphrase' => ['nullable', 'string', 'max:255'],
        ];
    }
}
