<?php

declare(strict_types=1);

namespace App\Http\Requests\Crypto;

use App\Services\Crypto\QuizService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuizEvaluateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'module' => ['required', Rule::in(array_keys(app(QuizService::class)->modules()))],
            'answers' => ['required', 'array'],
            'answers.*' => ['nullable', 'string', 'max:10'],
        ];
    }
}
