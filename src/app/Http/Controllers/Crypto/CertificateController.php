<?php

declare(strict_types=1);

namespace App\Http\Controllers\Crypto;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Crypto\Concerns\HandlesCryptoResponses;
use App\Http\Requests\Crypto\CertificateGenerateRequest;
use App\Http\Requests\Crypto\CertificateInspectRequest;
use App\Services\Crypto\CryptoOperationService;
use App\Services\Crypto\X509Service;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class CertificateController extends Controller
{
    use HandlesCryptoResponses;

    public function __construct(
        private readonly X509Service $x509,
        private readonly CryptoOperationService $operations,
    ) {}

    public function show(): View
    {
        return view('crypto.asymmetric.certificates');
    }

    public function generate(CertificateGenerateRequest $request): JsonResponse
    {
        try {
            $result = $this->x509->generateSelfSigned(
                $request->string('common_name')->toString(),
                $request->filled('organization') ? $request->string('organization')->toString() : 'CriptoLab',
                $request->filled('country') ? strtoupper($request->string('country')->toString()) : 'CO',
                $request->integer('days', 365),
            );

            $this->operations->log('x509_generate', 'rsa-2048-self-signed', $request->user(), 'lab-certificates');

            return $this->cryptoSuccess($result);
        } catch (\Throwable $exception) {
            return $this->cryptoFailure($exception);
        }
    }

    public function inspect(CertificateInspectRequest $request): JsonResponse
    {
        try {
            $result = $this->x509->inspect($request->string('certificate')->toString());
            $this->operations->log('x509_inspect', 'x509', $request->user(), 'lab-certificates');

            return $this->cryptoSuccess($result);
        } catch (\Throwable $exception) {
            return $this->cryptoFailure($exception);
        }
    }
}
