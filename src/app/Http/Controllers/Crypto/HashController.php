<?php

declare(strict_types=1);

namespace App\Http\Controllers\Crypto;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Crypto\Concerns\HandlesCryptoResponses;
use App\Http\Requests\Crypto\HashDigestRequest;
use App\Http\Requests\Crypto\HmacRequest;
use App\Http\Requests\Crypto\PasswordHashRequest;
use App\Http\Requests\Crypto\PasswordVerifyRequest;
use App\Services\Crypto\CryptoOperationService;
use App\Services\Crypto\Hash\HashService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class HashController extends Controller
{
    use HandlesCryptoResponses;

    public function __construct(
        private readonly HashService $hashes,
        private readonly CryptoOperationService $operations,
    ) {}

    public function show(): View
    {
        return view('crypto.hash', [
            'algorithms' => $this->hashes->available(),
        ]);
    }

    public function digest(HashDigestRequest $request): JsonResponse
    {
        try {
            $algorithm = $request->string('algorithm')->toString();
            $result = $this->hashes->digest($algorithm, $request->string('text')->toString());

            $this->operations->log('hash', $algorithm, $request->user(), 'lab-hash');

            return $this->cryptoSuccess(['result' => $result]);
        } catch (\Throwable $exception) {
            return $this->cryptoFailure($exception);
        }
    }

    public function hmac(HmacRequest $request): JsonResponse
    {
        try {
            $result = $this->hashes->hmac(
                $request->string('text')->toString(),
                $request->string('key')->toString(),
            );

            $valid = null;

            if ($request->filled('expected')) {
                $valid = $this->hashes->verifyHmac(
                    $request->string('text')->toString(),
                    $request->string('key')->toString(),
                    $request->string('expected')->toString(),
                );
            }

            $this->operations->log('hmac', 'hmac-sha256', $request->user(), 'lab-hash');

            return $this->cryptoSuccess([
                'result' => $result,
                'valid' => $valid,
            ]);
        } catch (\Throwable $exception) {
            return $this->cryptoFailure($exception);
        }
    }

    public function password(PasswordHashRequest $request): JsonResponse
    {
        try {
            $algorithm = $request->string('algorithm')->toString();
            $result = $this->hashes->passwordHash(
                $algorithm,
                $request->string('password')->toString(),
            );

            $this->operations->log('password_hash', $algorithm, $request->user(), 'lab-hash');

            return $this->cryptoSuccess(['result' => $result]);
        } catch (\Throwable $exception) {
            return $this->cryptoFailure($exception);
        }
    }

    public function verifyPassword(PasswordVerifyRequest $request): JsonResponse
    {
        try {
            $valid = $this->hashes->verifyPassword(
                $request->string('password')->toString(),
                $request->string('hash')->toString(),
            );

            $this->operations->log('password_verify', 'password_verify', $request->user(), 'lab-hash', $valid);

            return $this->cryptoSuccess([
                'valid' => $valid,
                'message' => $valid
                    ? 'La contraseña coincide con el hash.'
                    : 'La contraseña no coincide con el hash.',
            ]);
        } catch (\Throwable $exception) {
            return $this->cryptoFailure($exception);
        }
    }
}
