<?php

declare(strict_types=1);

namespace App\Http\Controllers\Crypto;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Crypto\Concerns\HandlesCryptoResponses;
use App\Http\Requests\Crypto\SignDecryptRequest;
use App\Http\Requests\Crypto\SignEncryptRequest;
use App\Services\Crypto\CryptoOperationService;
use App\Services\Crypto\Dto\CryptoEnvelope;
use App\Services\Crypto\Registries\AsymmetricCipherRegistry;
use App\Services\Crypto\Registries\SignerRegistry;
use App\Services\Crypto\SignedEncryptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class SignEncryptController extends Controller
{
    use HandlesCryptoResponses;

    public function __construct(
        private readonly SignedEncryptionService $signedEncryption,
        private readonly SignerRegistry $signers,
        private readonly AsymmetricCipherRegistry $ciphers,
        private readonly CryptoOperationService $operations,
    ) {}

    public function show(): View
    {
        return view('crypto.asymmetric.sign-encrypt', [
            'signerCatalog' => $this->signers->catalog(),
            'cipherCatalog' => $this->ciphers->catalog(),
        ]);
    }

    public function generatePairs(): JsonResponse
    {
        $pairs = $this->signedEncryption->generatePairs();

        $this->operations->log('keygen', 'sign+encrypt', auth()->user(), 'lab-sign-encrypt');

        return $this->cryptoSuccess($pairs);
    }

    public function encrypt(SignEncryptRequest $request): JsonResponse
    {
        try {
            $envelope = $this->signedEncryption->signAndEncrypt(
                $request->string('message')->toString(),
                $request->string('signer_algorithm')->toString(),
                $request->string('signing_private_key')->toString(),
                $request->string('signing_public_key')->toString(),
                $request->string('cipher_algorithm')->toString(),
                $request->string('encryption_public_key')->toString(),
                $request->filled('passphrase') ? $request->string('passphrase')->toString() : null,
            );

            $this->operations->log('sign_encrypt', $envelope->algorithm, $request->user(), 'lab-sign-encrypt');

            return $this->cryptoSuccess([
                'envelope' => $envelope->toArray(),
                'envelope_json' => $envelope->toJson(),
            ]);
        } catch (\Throwable $exception) {
            return $this->cryptoFailure($exception);
        }
    }

    public function decrypt(SignDecryptRequest $request): JsonResponse
    {
        try {
            $envelope = CryptoEnvelope::fromJson($request->string('payload')->toString());
            $result = $this->signedEncryption->decryptAndVerify(
                $envelope,
                $request->string('cipher_algorithm')->toString(),
                $request->string('encryption_private_key')->toString(),
                $request->filled('passphrase') ? $request->string('passphrase')->toString() : null,
                $request->filled('expected_signer_public_key') ? $request->string('expected_signer_public_key')->toString() : null,
            );

            $this->operations->log('decrypt_verify', $request->string('cipher_algorithm')->toString(), $request->user(), 'lab-sign-encrypt', $result['signature_valid']);

            return $this->cryptoSuccess($result);
        } catch (\Throwable $exception) {
            return $this->cryptoFailure($exception);
        }
    }
}
