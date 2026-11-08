<?php

namespace App\Http\Requests\Auth;

use App\Rules\UniqueEmailHash;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z0-9_.-]+$/',
                'unique:users,username',
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                new UniqueEmailHash,
            ],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'username' => Str::lower(trim((string) $this->input('username'))),
            'email' => Str::lower(trim((string) $this->input('email'))),
        ]);
    }

    public function messages(): array
    {
        return [
            'username.regex' => 'El usuario solo puede contener letras minúsculas, números, punto, guion y guion bajo.',
            'username.unique' => 'Ese nombre de usuario ya está en uso.',
            'email.unique' => 'El correo electrónico ya está registrado.',
        ];
    }
}
