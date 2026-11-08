<?php

declare(strict_types=1);

namespace App\Http\Controllers\Crypto\Concerns;

use App\Exceptions\CryptoException;
use Illuminate\Http\JsonResponse;
use Throwable;

trait HandlesCryptoResponses
{
    /**
     * @param  array<string, mixed>  $data
     */
    protected function cryptoSuccess(array $data, int $status = 200): JsonResponse
    {
        return response()->json(['ok' => true, ...$data], $status);
    }

    protected function cryptoFailure(Throwable $exception): JsonResponse
    {
        return response()->json([
            'ok' => false,
            'message' => $exception instanceof CryptoException
                ? $exception->getMessage()
                : 'No se pudo completar la operación criptográfica.',
        ], 422);
    }
}
