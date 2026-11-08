<?php

declare(strict_types=1);

namespace App\Http\Requests\Crypto;

use Illuminate\Foundation\Http\FormRequest;

class HmacRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:100000'],
            'key' => ['required', 'string', 'max:10000'],
            'expected' => ['nullable', 'string', 'max:128'],
        ];
    }
}
