<?php

declare(strict_types=1);

namespace App\Http\Controllers\Crypto;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Crypto\Concerns\HandlesCryptoResponses;
use App\Http\Requests\Crypto\FileDecryptRequest;
use App\Http\Requests\Crypto\FileEncryptRequest;
use App\Services\Crypto\CryptoOperationService;
use App\Services\Crypto\Dto\CryptoEnvelope;
use App\Services\Crypto\FileCryptoService;
use App\Services\Crypto\Registries\SymmetricCipherRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class FileCryptoController extends Controller
{
    use HandlesCryptoResponses;

    public function __construct(
        private readonly FileCryptoService $files,
        private readonly SymmetricCipherRegistry $ciphers,
        private readonly CryptoOperationService $operations,
    ) {}

    public function show(): View
    {
        return view('crypto.symmetric.files', [
            'catalog' => array_values(array_filter(
                $this->ciphers->catalog(),
                fn (array $cipher): bool => ($cipher['metadata']['authenticated'] ?? false) === true,
            )),
            'maxBytes' => $this->files->maxBytes(),
        ]);
    }

    public function encrypt(FileEncryptRequest $request): JsonResponse
    {
        try {
            $algorithm = $request->string('algorithm')->toString();
            $result = $this->files->encrypt(
                $request->file('file'),
                $algorithm,
                $request->string('key')->toString(),
            );

            $this->operations->log('file_encrypt', $algorithm, $request->user(), 'lab-files');

            return $this->cryptoSuccess([
                'algorithm' => $algorithm,
                'envelope' => $result['envelope']->toArray(),
                'envelope_json' => $result['envelope']->toJson(),
                'original_name' => $result['original_name'],
                'mime' => $result['mime'],
                'original_bytes' => $result['original_bytes'],
                'ciphertext_bytes' => $result['ciphertext_bytes'],
            ]);
        } catch (\Throwable $exception) {
            return $this->cryptoFailure($exception);
        }
    }

    public function decrypt(FileDecryptRequest $request): JsonResponse
    {
        try {
            $file = $request->file('file');
            $contents = $file->get();

            if ($contents === false) {
                throw new \RuntimeException('No se pudo leer el archivo cifrado.');
            }

            $envelope = CryptoEnvelope::fromJson($contents);
            $algorithm = $request->string('algorithm')->toString();
            $result = $this->files->decrypt($envelope, $algorithm, $request->string('key')->toString());

            $this->operations->log('file_decrypt', $algorithm, $request->user(), 'lab-files');

            return $this->cryptoSuccess([
                'algorithm' => $algorithm,
                'name' => $result['name'],
                'mime' => $result['mime'],
                'bytes' => $result['bytes'],
                'contents_base64' => base64_encode($result['contents']),
            ]);
        } catch (\Throwable $exception) {
            return $this->cryptoFailure($exception);
        }
    }
}
