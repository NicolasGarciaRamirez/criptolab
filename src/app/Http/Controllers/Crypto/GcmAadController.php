<?php

declare(strict_types=1);

namespace App\Http\Controllers\Crypto;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Crypto\Concerns\HandlesCryptoResponses;
use App\Http\Requests\Crypto\AadDecryptRequest;
use App\Http\Requests\Crypto\AadEncryptRequest;
use App\Services\Crypto\CryptoOperationService;
use App\Services\Crypto\Dto\CryptoEnvelope;
use App\Services\Crypto\Symmetric\Aes256Gcm;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class GcmAadController extends Controller
{
    use HandlesCryptoResponses;

    public function __construct(
        private readonly Aes256Gcm $aes,
        private readonly CryptoOperationService $operations,
    ) {}

    public function show(): View
    {
        return view('crypto.symmetric.aad');
    }

    public function encrypt(AadEncryptRequest $request): JsonResponse
    {
        try {
            $aad = $request->string('aad')->toString();
            $envelope = $this->aes->encryptWithAad(
                $request->string('plaintext')->toString(),
                $request->string('key')->toString(),
                $aad,
            );

            $this->operations->log('gcm_aad_encrypt', 'aes-256-gcm', $request->user(), 'lab-aad');

            return $this->cryptoSuccess([
                'envelope' => $envelope->toArray(),
                'envelope_json' => $envelope->toJson(),
                'aad' => $aad,
            ]);
        } catch (\Throwable $exception) {
            return $this->cryptoFailure($exception);
        }
    }

    public function decrypt(AadDecryptRequest $request): JsonResponse
    {
        try {
            $envelope = CryptoEnvelope::fromJson($request->string('payload')->toString());
            $plaintext = $this->aes->decryptWithAad(
                $envelope,
                $request->string('key')->toString(),
                $request->string('aad')->toString(),
            );

            $this->operations->log('gcm_aad_decrypt', 'aes-256-gcm', $request->user(), 'lab-aad');

            return $this->cryptoSuccess([
                'plaintext' => $plaintext,
                'bytes' => strlen($plaintext),
            ]);
        } catch (\Throwable $exception) {
            return $this->cryptoFailure($exception);
        }
    }
}
