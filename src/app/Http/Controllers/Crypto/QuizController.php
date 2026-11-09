<?php

declare(strict_types=1);

namespace App\Http\Controllers\Crypto;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Crypto\Concerns\HandlesCryptoResponses;
use App\Http\Requests\Crypto\QuizEvaluateRequest;
use App\Services\Crypto\CryptoOperationService;
use App\Services\Crypto\QuizService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuizController extends Controller
{
    use HandlesCryptoResponses;

    public function __construct(
        private readonly QuizService $quiz,
        private readonly CryptoOperationService $operations,
    ) {}

    public function show(Request $request): View
    {
        $modules = $this->quiz->modules();
        $module = $request->string('modulo')->toString();

        if (! array_key_exists($module, $modules)) {
            $module = array_key_first($modules);
        }

        return view('crypto.quiz', [
            'modules' => $modules,
            'module' => $module,
            'questions' => $this->quiz->questions($module),
        ]);
    }

    public function evaluate(QuizEvaluateRequest $request): JsonResponse
    {
        try {
            $module = $request->string('module')->toString();
            $result = $this->quiz->evaluate($module, $request->array('answers'));

            $this->operations->log('quiz_evaluate', $module, $request->user(), 'lab-quiz');

            return $this->cryptoSuccess(['result' => $result]);
        } catch (\Throwable $exception) {
            return $this->cryptoFailure($exception);
        }
    }
}
