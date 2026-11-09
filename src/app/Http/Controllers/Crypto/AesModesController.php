<?php

declare(strict_types=1);

namespace App\Http\Controllers\Crypto;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Crypto\Concerns\HandlesCryptoResponses;
use App\Http\Requests\Crypto\ModeComparisonRequest;
use App\Http\Requests\Crypto\NonceReuseRequest;
use App\Services\Crypto\AesModesComparisonService;
use App\Services\Crypto\CryptoOperationService;
use App\Services\Crypto\NonceReuseDemoService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class AesModesController extends Controller
{
    use HandlesCryptoResponses;

    public function __construct(
        private readonly AesModesComparisonService $modes,
        private readonly NonceReuseDemoService $nonceReuse,
        private readonly CryptoOperationService $operations,
    ) {}

    public function show(): View
    {
        return view('crypto.symmetric.modes');
    }

    public function compare(ModeComparisonRequest $request): JsonResponse
    {
        try {
            $result = $this->modes->compare($request->string('plaintext')->toString());
            $this->operations->log('modes_compare', 'aes-modes', $request->user(), 'lab-modes');

            return $this->cryptoSuccess(['result' => $result]);
        } catch (\Throwable $exception) {
            return $this->cryptoFailure($exception);
        }
    }

    public function nonceReuse(NonceReuseRequest $request): JsonResponse
    {
        try {
            $algorithm = $request->string('algorithm')->toString();
            $result = $this->nonceReuse->demonstrate(
                $algorithm,
                $request->string('first')->toString(),
                $request->string('second')->toString(),
            );

            $this->operations->log('nonce_reuse_demo', $algorithm, $request->user(), 'lab-modes');

            return $this->cryptoSuccess(['result' => $result]);
        } catch (\Throwable $exception) {
            return $this->cryptoFailure($exception);
        }
    }
}
