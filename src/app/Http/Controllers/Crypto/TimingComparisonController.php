<?php

declare(strict_types=1);

namespace App\Http\Controllers\Crypto;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Crypto\Concerns\HandlesCryptoResponses;
use App\Http\Requests\Crypto\TimingCompareRequest;
use App\Services\Crypto\CryptoOperationService;
use App\Services\Crypto\TimingComparisonService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class TimingComparisonController extends Controller
{
    use HandlesCryptoResponses;

    public function __construct(
        private readonly TimingComparisonService $timing,
        private readonly CryptoOperationService $operations,
    ) {}

    public function show(): View
    {
        return view('crypto.hash.timing');
    }

    public function compare(TimingCompareRequest $request): JsonResponse
    {
        try {
            $result = $this->timing->compare(
                $request->string('first')->toString(),
                $request->string('second')->toString(),
                $request->integer('iterations', 50000),
            );

            $this->operations->log('timing_compare', 'php-comparison', $request->user(), 'lab-timing');

            return $this->cryptoSuccess(['result' => $result]);
        } catch (\Throwable $exception) {
            return $this->cryptoFailure($exception);
        }
    }
}
