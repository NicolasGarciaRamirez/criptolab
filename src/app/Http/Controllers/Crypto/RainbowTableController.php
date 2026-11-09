<?php

declare(strict_types=1);

namespace App\Http\Controllers\Crypto;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Crypto\Concerns\HandlesCryptoResponses;
use App\Http\Requests\Crypto\RainbowRequest;
use App\Services\Crypto\CryptoOperationService;
use App\Services\Crypto\RainbowTableService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class RainbowTableController extends Controller
{
    use HandlesCryptoResponses;

    public function __construct(
        private readonly RainbowTableService $rainbow,
        private readonly CryptoOperationService $operations,
    ) {}

    public function show(): View
    {
        return view('crypto.hash.rainbow', [
            'dictionary' => $this->rainbow->dictionary(),
        ]);
    }

    public function consult(RainbowRequest $request): JsonResponse
    {
        try {
            $password = $request->string('password')->toString();
            $lookup = $this->rainbow->lookup($password);
            $argon = $this->rainbow->argon2id($password);

            $this->operations->log('rainbow_lookup', 'md5-sha1-argon2id', $request->user(), 'lab-rainbow');

            return $this->cryptoSuccess([
                'lookup' => $lookup,
                'argon2id' => $argon,
            ]);
        } catch (\Throwable $exception) {
            return $this->cryptoFailure($exception);
        }
    }
}
