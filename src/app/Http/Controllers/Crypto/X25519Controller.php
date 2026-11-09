<?php

declare(strict_types=1);

namespace App\Http\Controllers\Crypto;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Crypto\Concerns\HandlesCryptoResponses;
use App\Http\Requests\Crypto\X25519SharedRequest;
use App\Services\Crypto\CryptoOperationService;
use App\Services\Crypto\KeyAgreementService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class X25519Controller extends Controller
{
    use HandlesCryptoResponses;

    public function __construct(
        private readonly KeyAgreementService $agreement,
        private readonly CryptoOperationService $operations,
    ) {}

    public function show(): View
    {
        return view('crypto.asymmetric.x25519');
    }

    public function generate(): JsonResponse
    {
        $alice = $this->agreement->generatePair();
        $bob = $this->agreement->generatePair();

        $this->operations->log('key_exchange', 'x25519', auth()->user(), 'lab-x25519');

        return $this->cryptoSuccess([
            'alice' => $alice,
            'bob' => $bob,
        ]);
    }

    public function shared(X25519SharedRequest $request): JsonResponse
    {
        try {
            $aliceShared = $this->agreement->sharedSecret(
                $request->string('alice_private_key')->toString(),
                $request->string('bob_public_key')->toString(),
            );
            $bobShared = $this->agreement->sharedSecret(
                $request->string('bob_private_key')->toString(),
                $request->string('alice_public_key')->toString(),
            );

            $aliceKey = $this->agreement->deriveAesKey($aliceShared);
            $bobKey = $this->agreement->deriveAesKey($bobShared);
            $message = $request->string('message')->toString();
            $envelope = null;
            $decrypted = null;

            if ($message !== '') {
                $envelope = $this->agreement->encryptMessage($message, $aliceShared);
                $decrypted = $this->agreement->decryptMessage($envelope, $bobShared);
            }

            $this->operations->log('key_exchange', 'x25519', $request->user(), 'lab-x25519');

            return $this->cryptoSuccess([
                'alice_shared' => $aliceShared,
                'bob_shared' => $bobShared,
                'same_secret' => hash_equals($aliceShared, $bobShared),
                'alice_fingerprint' => $this->agreement->fingerprint($aliceShared),
                'bob_fingerprint' => $this->agreement->fingerprint($bobShared),
                'alice_key' => $aliceKey,
                'bob_key' => $bobKey,
                'same_key' => hash_equals($aliceKey['key'], $bobKey['key']),
                'envelope' => $envelope?->toArray(),
                'envelope_json' => $envelope?->toJson(),
                'decrypted' => $decrypted,
            ]);
        } catch (\Throwable $exception) {
            return $this->cryptoFailure($exception);
        }
    }
}
