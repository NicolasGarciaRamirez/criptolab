<?php

declare(strict_types=1);

namespace App\Http\Requests\Crypto;

use App\Services\Crypto\Hash\HashService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HashDigestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'algorithm' => ['required', 'string', Rule::in(array_keys(app(HashService::class)->available()))],
            'text' => ['required', 'string', 'max:100000'],
        ];
    }
}
