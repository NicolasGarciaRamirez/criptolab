<?php

namespace App\Http\Requests;

use App\Rules\UniqueEmailHash;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
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
                Rule::unique('users', 'username')->ignore($this->user()->id),
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                new UniqueEmailHash($this->user()->id),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
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
        ];
    }
}
