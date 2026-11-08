<?php

declare(strict_types=1);

namespace App\Http\Controllers\Crypto;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Crypto\Concerns\HandlesCryptoResponses;
use App\Http\Requests\Crypto\DeriveKeyRequest;
use App\Http\Requests\Crypto\SymmetricAlgorithmRequest;
use App\Http\Requests\Crypto\SymmetricDecryptRequest;
use App\Http\Requests\Crypto\SymmetricEncryptRequest;
use App\Services\Crypto\CryptoOperationService;
use App\Services\Crypto\Dto\CryptoEnvelope;
use App\Services\Crypto\KeyDerivationService;
use App\Services\Crypto\Registries\SymmetricCipherRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class SymmetricController extends Controller
{
    use HandlesCryptoResponses;

    public function __construct(
        private readonly SymmetricCipherRegistry $registry,
        private readonly CryptoOperationService $operations,
    ) {}

    public function show(): View
    {
        return view('crypto.symmetric', [
            'catalog' => $this->registry->catalog(),
        ]);
    }

    public function generateKey(SymmetricAlgorithmRequest $request): JsonResponse
    {
        $cipher = $this->registry->get($request->string('algorithm')->toString());
        $key = $cipher->generateKey();
        $raw = base64_decode($key, true);

        $this->operations->log('keygen', $cipher->id(), context: 'lab-symmetric');

        return $this->cryptoSuccess([
            'algorithm' => $cipher->id(),
            'key' => $key,
            'key_hex' => bin2hex($raw),
            'key_bytes' => strlen($raw),
        ]);
    }

    public function encrypt(SymmetricEncryptRequest $request): JsonResponse
    {
        try {
            $cipher = $this->registry->get($request->string('algorithm')->toString());
            $plaintext = $request->string('plaintext')->toString();
            $key = $request->string('key')->toString();
            $envelope = $cipher->encrypt($plaintext, $key);

            $this->operations->log('encrypt', $cipher->id(), context: 'lab-symmetric');

            return $this->cryptoSuccess([
                'algorithm' => $cipher->id(),
                'label' => $cipher->label(),
                'envelope' => $envelope->toArray(),
                'envelope_json' => $envelope->toJson(),
                'combined' => $envelope->combined(),
                'sizes' => [
                    'plaintext' => strlen($plaintext),
                    'key' => $cipher->keyBytes(),
                    'nonce' => $cipher->nonceBytes(),
                    ...$envelope->sizes(),
                ],
            ]);
        } catch (\Throwable $exception) {
            return $this->cryptoFailure($exception);
        }
    }

    public function decrypt(SymmetricDecryptRequest $request): JsonResponse
    {
        try {
            $cipher = $this->registry->get($request->string('algorithm')->toString());
            $envelope = CryptoEnvelope::fromJson($request->string('payload')->toString());
            $plaintext = $cipher->decrypt($envelope, $request->string('key')->toString());

            $this->operations->log('decrypt', $cipher->id(), context: 'lab-symmetric');

            return $this->cryptoSuccess([
                'algorithm' => $cipher->id(),
                'label' => $cipher->label(),
                'plaintext' => $plaintext,
                'sizes' => [
                    'plaintext' => strlen($plaintext),
                    ...$envelope->sizes(),
                ],
            ]);
        } catch (\Throwable $exception) {
            return $this->cryptoFailure($exception);
        }
    }

    public function demonstrateIv(SymmetricEncryptRequest $request): JsonResponse
    {
        try {
            $cipher = $this->registry->get($request->string('algorithm')->toString());
            $plaintext = $request->string('plaintext')->toString();
            $key = $request->string('key')->toString();
            $first = $cipher->encrypt($plaintext, $key);
            $second = $cipher->encrypt($plaintext, $key);

            $this->operations->log('iv_demo', $cipher->id(), context: 'lab-symmetric');

            return $this->cryptoSuccess([
                'algorithm' => $cipher->id(),
                'label' => $cipher->label(),
                'first' => $first->toArray(),
                'second' => $second->toArray(),
                'different_iv' => $first->iv !== $second->iv,
                'different_ciphertext' => $first->ciphertext !== $second->ciphertext,
                'iv_bytes' => $cipher->nonceBytes(),
            ]);
        } catch (\Throwable $exception) {
            return $this->cryptoFailure($exception);
        }
    }

    public function derive(DeriveKeyRequest $request): JsonResponse
    {
        try {
            $kdf = app(KeyDerivationService::class);
            $method = $request->string('method')->toString();
            $passphrase = $request->string('passphrase')->toString();
            $salt = $request->filled('salt') ? $request->string('salt')->toString() : null;
            $bytes = $request->integer('bytes');
            $iterations = $request->integer('iterations', 200000);

            $result = $method === 'pbkdf2'
                ? $kdf->pbkdf2($passphrase, $salt, $iterations, $bytes)
                : $kdf->argon2id($passphrase, $salt, $bytes);

            $this->operations->log('derive', $method, context: 'lab-symmetric');

            return $this->cryptoSuccess([
                'derivation' => $result,
            ]);
        } catch (\Throwable $exception) {
            return $this->cryptoFailure($exception);
        }
    }
}
