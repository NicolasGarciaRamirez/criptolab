<?php

declare(strict_types=1);

namespace App\Http\Requests\Crypto;

use App\Services\Crypto\Registries\AsymmetricCipherRegistry;
use App\Services\Crypto\Registries\SymmetricCipherRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AuthDemoStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'max:190'],
            'sym_algorithm' => ['required', 'string', Rule::in(array_keys(app(SymmetricCipherRegistry::class)->all()))],
            'asym_algorithm' => ['required', 'string', Rule::in(array_keys(app(AsymmetricCipherRegistry::class)->all()))],
            'swap' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => 'Escribe un nombre de usuario para la demostración.',
            'password.required' => 'Escribe una contraseña de demostración.',
            'password.max' => 'La contraseña de demostración no puede superar 190 caracteres por el límite de RSA-OAEP.',
        ];
    }
}
