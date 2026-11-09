<?php

declare(strict_types=1);

namespace App\Http\Controllers\Crypto;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Crypto\Concerns\HandlesCryptoResponses;
use App\Services\Crypto\BenchmarkService;
use App\Services\Crypto\CryptoOperationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BenchmarkController extends Controller
{
    use HandlesCryptoResponses;

    public function __construct(
        private readonly BenchmarkService $benchmark,
        private readonly CryptoOperationService $operations,
    ) {}

    public function show(): View
    {
        return view('crypto.benchmark');
    }

    public function run(Request $request): JsonResponse
    {
        try {
            $result = $this->benchmark->run();
            $this->operations->log('benchmark', 'suite', $request->user(), 'lab-benchmark');

            return $this->cryptoSuccess($result);
        } catch (\Throwable $exception) {
            return $this->cryptoFailure($exception);
        }
    }
}
