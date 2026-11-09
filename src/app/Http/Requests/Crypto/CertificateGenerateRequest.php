<?php

declare(strict_types=1);

namespace App\Http\Requests\Crypto;

use Illuminate\Foundation\Http\FormRequest;

class CertificateGenerateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'common_name' => ['required', 'string', 'max:100'],
            'organization' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'size:2'],
            'days' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ];
    }
}
