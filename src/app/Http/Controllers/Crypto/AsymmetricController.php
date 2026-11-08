<?php

declare(strict_types=1);

namespace App\Http\Controllers\Crypto;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Crypto\Concerns\HandlesCryptoResponses;
use App\Http\Requests\Crypto\AsymmetricDecryptRequest;
use App\Http\Requests\Crypto\AsymmetricEncryptRequest;
use App\Http\Requests\Crypto\AsymmetricKeygenRequest;
use App\Http\Requests\Crypto\SignerKeygenRequest;
use App\Http\Requests\Crypto\SignRequest;
use App\Http\Requests\Crypto\VerifyRequest;
use App\Services\Audit\AuditService;
use App\Services\Crypto\CryptoOperationService;
use App\Services\Crypto\Dto\CryptoEnvelope;
use App\Services\Crypto\KeypairService;
use App\Services\Crypto\Registries\AsymmetricCipherRegistry;
use App\Services\Crypto\Registries\SignerRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class AsymmetricController extends Controller
{
    use HandlesCryptoResponses;

    public function __construct(
        private readonly AsymmetricCipherRegistry $ciphers,
        private readonly SignerRegistry $signers,
        private readonly KeypairService $keypairs,
        private readonly CryptoOperationService $operations,
        private readonly AuditService $audit,
    ) {}

    public function show(): View
    {
        return view('crypto.asymmetric', [
            'cipherCatalog' => $this->ciphers->catalog(),
            'signerCatalog' => $this->signers->catalog(),
        ]);
    }

    public function generateKeypair(AsymmetricKeygenRequest $request): JsonResponse
    {
        try {
            $algorithm = $request->string('algorithm')->toString();
            $save = $request->boolean('save');
            $result = $this->keypairs->generateCipherKeypair(
                $algorithm,
                $request->filled('name') ? $request->string('name')->toString() : null,
                $request->user(),
                $save,
                $request->filled('passphrase') ? $request->string('passphrase')->toString() : null,
            );

            $this->operations->log('keygen', $algorithm, $request->user(), 'lab-asymmetric');

            if ($save && $result['keypair'] !== null) {
                $this->audit->log($request->user(), 'keypair_created', 'Par de llaves guardado', [
                    'algorithm' => $algorithm,
                    'keypair_id' => $result['keypair']->id,
                ]);
            }

            return $this->cryptoSuccess([
                'algorithm' => $algorithm,
                'public_key' => $result['pair']->publicKey,
                'private_key' => $result['pair']->privateKey,
                'fingerprint' => $result['fingerprint'],
                'saved' => $result['keypair'] !== null,
                'keypair_id' => $result['keypair']?->id,
                'private_key_notice' => 'La llave privada se muestra una sola vez. Guárdala en un lugar seguro.',
                'meta' => $result['pair']->meta,
            ]);
        } catch (\Throwable $exception) {
            return $this->cryptoFailure($exception);
        }
    }

    public function generateSignerKeypair(SignerKeygenRequest $request): JsonResponse
    {
        try {
            $algorithm = $request->string('algorithm')->toString();
            $save = $request->boolean('save');
            $result = $this->keypairs->generateSignerKeypair(
                $algorithm,
                $request->filled('name') ? $request->string('name')->toString() : null,
                $request->user(),
                $save,
            );

            $this->operations->log('keygen', $algorithm, $request->user(), 'lab-signature');

            if ($save && $result['keypair'] !== null) {
                $this->audit->log($request->user(), 'keypair_created', 'Par de llaves de firma guardado', [
                    'algorithm' => $algorithm,
                    'keypair_id' => $result['keypair']->id,
                ]);
            }

            return $this->cryptoSuccess([
                'algorithm' => $algorithm,
                'public_key' => $result['pair']->publicKey,
                'private_key' => $result['pair']->privateKey,
                'fingerprint' => $result['fingerprint'],
                'saved' => $result['keypair'] !== null,
                'keypair_id' => $result['keypair']?->id,
                'private_key_notice' => 'La llave privada se muestra una sola vez. Guárdala en un lugar seguro.',
                'meta' => $result['pair']->meta,
            ]);
        } catch (\Throwable $exception) {
            return $this->cryptoFailure($exception);
        }
    }

    public function encrypt(AsymmetricEncryptRequest $request): JsonResponse
    {
        try {
            $cipher = $this->ciphers->get($request->string('algorithm')->toString());
            $plaintext = $request->string('plaintext')->toString();
            $envelope = $cipher->encrypt($plaintext, $request->string('public_key')->toString());

            $this->operations->log('encrypt', $cipher->id(), $request->user(), 'lab-asymmetric');

            return $this->cryptoSuccess([
                'algorithm' => $cipher->id(),
                'label' => $cipher->label(),
                'envelope' => $envelope->toArray(),
                'envelope_json' => $envelope->toJson(),
                'sizes' => [
                    'plaintext' => strlen($plaintext),
                    ...$envelope->sizes(),
                ],
                'max_plaintext_bytes' => $cipher->maxPlaintextBytes(),
            ]);
        } catch (\Throwable $exception) {
            return $this->cryptoFailure($exception);
        }
    }

    public function decrypt(AsymmetricDecryptRequest $request): JsonResponse
    {
        try {
            $cipher = $this->ciphers->get($request->string('algorithm')->toString());
            $envelope = CryptoEnvelope::fromJson($request->string('payload')->toString());
            $plaintext = $cipher->decrypt(
                $envelope,
                $request->string('private_key')->toString(),
                $request->filled('passphrase') ? $request->string('passphrase')->toString() : null,
            );

            $this->operations->log('decrypt', $cipher->id(), $request->user(), 'lab-asymmetric');

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

    public function sign(SignRequest $request): JsonResponse
    {
        try {
            $signer = $this->signers->get($request->string('algorithm')->toString());
            $signature = $signer->sign(
                $request->string('message')->toString(),
                $request->string('private_key')->toString(),
                $request->filled('passphrase') ? $request->string('passphrase')->toString() : null,
            );

            $this->operations->log('sign', $signer->id(), $request->user(), 'lab-signature');

            return $this->cryptoSuccess([
                'algorithm' => $signer->id(),
                'label' => $signer->label(),
                'signature' => $signature,
                'signature_bytes' => strlen(base64_decode($signature, true) ?: ''),
            ]);
        } catch (\Throwable $exception) {
            return $this->cryptoFailure($exception);
        }
    }

    public function verify(VerifyRequest $request): JsonResponse
    {
        try {
            $signer = $this->signers->get($request->string('algorithm')->toString());
            $valid = $signer->verify(
                $request->string('message')->toString(),
                $request->string('signature')->toString(),
                $request->string('public_key')->toString(),
            );

            $this->operations->log('verify', $signer->id(), $request->user(), 'lab-signature', $valid);

            return $this->cryptoSuccess([
                'algorithm' => $signer->id(),
                'label' => $signer->label(),
                'valid' => $valid,
                'message' => $valid
                    ? 'Firma válida: el mensaje y la firma coinciden.'
                    : 'Firma inválida: el mensaje, la firma o la llave pública fueron alterados.',
            ]);
        } catch (\Throwable $exception) {
            return $this->cryptoFailure($exception);
        }
    }
}
