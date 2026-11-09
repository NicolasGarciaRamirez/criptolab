<?php

declare(strict_types=1);

namespace App\Http\Requests\Crypto;

use Illuminate\Foundation\Http\FormRequest;

class TimingCompareRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'first' => ['required', 'string', 'max:5000'],
            'second' => ['required', 'string', 'max:5000'],
            'iterations' => ['nullable', 'integer', 'min:100', 'max:1000000'],
        ];
    }
}
