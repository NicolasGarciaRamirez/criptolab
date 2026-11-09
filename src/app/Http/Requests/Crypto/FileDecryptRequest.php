<?php

declare(strict_types=1);

namespace App\Http\Requests\Crypto;

use App\Services\Crypto\Registries\SymmetricCipherRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FileDecryptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:8192'],
            'algorithm' => ['required', 'string', Rule::in($this->authenticatedAlgorithms())],
            'key' => ['required', 'string', 'max:4096'],
        ];
    }

    /**
     * @return list<string>
     */
    private function authenticatedAlgorithms(): array
    {
        $ids = [];

        foreach (app(SymmetricCipherRegistry::class)->all() as $cipher) {
            if (($cipher->metadata()['authenticated'] ?? false) === true) {
                $ids[] = $cipher->id();
            }
        }

        return $ids;
    }
}
