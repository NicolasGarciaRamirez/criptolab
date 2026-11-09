<?php

declare(strict_types=1);

namespace App\Http\Controllers\Crypto;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Crypto\Concerns\HandlesCryptoResponses;
use App\Http\Requests\Crypto\KeyInspectRequest;
use App\Services\Audit\AuditService;
use App\Services\Crypto\CryptoOperationService;
use App\Services\Crypto\KeyInspectionService;
use App\Services\Crypto\KeypairService;
use App\Services\Crypto\Registries\AsymmetricCipherRegistry;
use App\Services\Crypto\Registries\SignerRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class KeyImportController extends Controller
{
    use HandlesCryptoResponses;

    public function __construct(
        private readonly KeyInspectionService $inspector,
        private readonly KeypairService $keypairs,
        private readonly AsymmetricCipherRegistry $ciphers,
        private readonly SignerRegistry $signers,
        private readonly CryptoOperationService $operations,
        private readonly AuditService $audit,
    ) {}

    public function show(): View
    {
        return view('crypto.asymmetric.import', [
            'cipherCatalog' => $this->ciphers->catalog(),
            'signerCatalog' => $this->signers->catalog(),
        ]);
    }

    public function inspect(KeyInspectRequest $request): JsonResponse
    {
        try {
            $publicInspection = $request->filled('public_key')
                ? $this->inspector->inspect($request->string('public_key')->toString())
                : null;
            $privateInspection = $request->filled('private_key')
                ? $this->inspector->inspect($request->string('private_key')->toString())
                : null;
            $inspection = $privateInspection ?? $publicInspection;

            if ($inspection === null) {
                throw new \RuntimeException('Debes indicar al menos una llave pública o privada.');
            }

            if ($publicInspection !== null && $privateInspection !== null && ! hash_equals($publicInspection['fingerprint'], $privateInspection['fingerprint'])) {
                throw new \RuntimeException('La llave pública y la privada no corresponden entre sí.');
            }

            $algorithm = $request->string('algorithm')->toString();
            $this->inspector->assertCompatible($algorithm, $inspection);

            $saved = null;

            if ($request->boolean('save')) {
                if ($privateInspection === null) {
                    throw new \RuntimeException('Para guardar se requiere la llave privada.');
                }

                $saved = $this->keypairs->saveImported(
                    $request->user(),
                    $algorithm,
                    $request->filled('name') ? $request->string('name')->toString() : 'Llave importada',
                    $inspection['public_key'],
                    $request->string('private_key')->toString(),
                );

                $this->audit->log($request->user(), 'keypair_created', 'Llave importada guardada', [
                    'algorithm' => $algorithm,
                    'keypair_id' => $saved->id,
                ]);
                $this->operations->log('key_import', $algorithm, $request->user(), 'lab-import');
            }

            return $this->cryptoSuccess([
                'inspection' => $inspection,
                'saved' => $saved !== null,
                'keypair_id' => $saved?->id,
            ]);
        } catch (\Throwable $exception) {
            return $this->cryptoFailure($exception);
        }
    }
}
