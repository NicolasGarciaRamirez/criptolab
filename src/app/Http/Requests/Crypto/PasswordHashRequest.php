<?php

declare(strict_types=1);

namespace App\Http\Requests\Crypto;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PasswordHashRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'algorithm' => ['required', Rule::in(['bcrypt', 'argon2id'])],
            'password' => ['required', 'string', 'max:1000'],
        ];
    }
}
