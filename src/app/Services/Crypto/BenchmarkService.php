<?php

declare(strict_types=1);

namespace App\Services\Crypto;

use App\Services\Crypto\Asymmetric\Ed25519Signer;
use App\Services\Crypto\Asymmetric\RsaOaep;
use App\Services\Crypto\Asymmetric\RsaSigner;
use App\Services\Crypto\Hash\HashService;
use App\Services\Crypto\Registries\AsymmetricCipherRegistry;
use App\Services\Crypto\Registries\SignerRegistry;
use App\Services\Crypto\Registries\SymmetricCipherRegistry;
use Throwable;

final class BenchmarkService
{
    public function __construct(
        private readonly SymmetricCipherRegistry $symmetric,
        private readonly AsymmetricCipherRegistry $asymmetric,
        private readonly SignerRegistry $signers,
        private readonly HashService $hashes,
        private readonly KeyAgreementService $keyAgreement,
    ) {}

    /**
     * @return array{generated_at: string, rows: list<array<string, mixed>>, note: string}
     */
    public function run(): array
    {
        $rows = [];
        $plaintext = 'CriptoLab benchmark de operaciones criptográficas.';
        $key = $this->symmetric->get('aes-256-gcm')->generateKey();
        $chachaKey = $this->symmetric->get('chacha20-poly1305')->generateKey();
        $xsalsaKey = $this->symmetric->get('xsalsa20-poly1305')->generateKey();

        $aes = $this->symmetric->get('aes-256-gcm');
        $chacha = $this->symmetric->get('chacha20-poly1305');
        $xsalsa = $this->symmetric->get('xsalsa20-poly1305');

        $aesEnvelope = $aes->encrypt($plaintext, $key);
        $chachaEnvelope = $chacha->encrypt($plaintext, $chachaKey);
        $xsalsaEnvelope = $xsalsa->encrypt($plaintext, $xsalsaKey);

        $rows[] = $this->row('simétrico', 'AES-256-GCM cifrar', 200, fn () => $aes->encrypt($plaintext, $key));
        $rows[] = $this->row('simétrico', 'AES-256-GCM descifrar', 200, fn () => $aes->decrypt($aesEnvelope, $key));
        $rows[] = $this->row('simétrico', 'ChaCha20-Poly1305 cifrar', 200, fn () => $chacha->encrypt($plaintext, $chachaKey));
        $rows[] = $this->row('simétrico', 'ChaCha20-Poly1305 descifrar', 200, fn () => $chacha->decrypt($chachaEnvelope, $chachaKey));
        $rows[] = $this->row('simétrico', 'XSalsa20-Poly1305 cifrar', 200, fn () => $xsalsa->encrypt($plaintext, $xsalsaKey));
        $rows[] = $this->row('simétrico', 'XSalsa20-Poly1305 descifrar', 200, fn () => $xsalsa->decrypt($xsalsaEnvelope, $xsalsaKey));

        $rows[] = $this->row('hash', 'SHA-256', 5000, fn () => $this->hashes->digest('sha256', $plaintext));
        $rows[] = $this->row('hash', 'HMAC-SHA256', 2000, fn () => $this->hashes->hmac($plaintext, 'llave-de-benchmark'));
        $rows[] = $this->row('hash', 'Argon2id', 1, fn () => $this->hashes->passwordHash('argon2id', 'PasswordBenchmark#2026'));

        $rsa = new RsaOaep(2048);
        $rsaPair = $rsa->generateKeyPair();
        $rsaEnvelope = $rsa->encrypt('mensaje corto', $rsaPair->publicKey);

        $rows[] = $this->row('asimétrico', 'RSA-2048-OAEP cifrar', 20, fn () => $rsa->encrypt('mensaje corto', $rsaPair->publicKey));
        $rows[] = $this->row('asimétrico', 'RSA-2048-OAEP descifrar', 20, fn () => $rsa->decrypt($rsaEnvelope, $rsaPair->privateKey));

        $rsaSigner = new RsaSigner(2048);
        $rsaSignPair = $rsaSigner->generateKeyPair();
        $rsaSignature = $rsaSigner->sign('documento de benchmark', $rsaSignPair->privateKey);

        $rows[] = $this->row('firma', 'RSA-SHA256 firmar', 50, fn () => $rsaSigner->sign('documento de benchmark', $rsaSignPair->privateKey));
        $rows[] = $this->row('firma', 'RSA-SHA256 verificar', 50, fn () => $rsaSigner->verify('documento de benchmark', $rsaSignature, $rsaSignPair->publicKey));

        $ed25519 = new Ed25519Signer;
        $edPair = $ed25519->generateKeyPair();
        $edSignature = $ed25519->sign('documento de benchmark', $edPair->privateKey);

        $rows[] = $this->row('firma', 'Ed25519 firmar', 1000, fn () => $ed25519->sign('documento de benchmark', $edPair->privateKey));
        $rows[] = $this->row('firma', 'Ed25519 verificar', 1000, fn () => $ed25519->verify('documento de benchmark', $edSignature, $edPair->publicKey));

        $alice = $this->keyAgreement->generatePair();
        $bob = $this->keyAgreement->generatePair();
        $shared = $this->keyAgreement->sharedSecret($alice['private_key'], $bob['public_key']);

        $rows[] = $this->row('intercambio', 'X25519 secreto compartido', 1000, fn () => $this->keyAgreement->sharedSecret($alice['private_key'], $bob['public_key']));
        $rows[] = $this->row('intercambio', 'HKDF SHA-256', 1000, fn () => $this->keyAgreement->deriveAesKey($shared));

        return [
            'generated_at' => now()->toIso8601String(),
            'rows' => $rows,
            'note' => 'Benchmark orientativo dentro de PHP-FPM. No es una medición científica; sirve para comparar órdenes de magnitud.',
        ];
    }

    /**
     * @return array{category: string, operation: string, iterations: int, total_ms: float, average_ms: float, operations_per_second: float, status: string}
     */
    private function row(string $category, string $operation, int $iterations, callable $callback): array
    {
        try {
            $start = hrtime(true);

            for ($i = 0; $i < $iterations; $i++) {
                $callback();
            }

            $totalMs = (hrtime(true) - $start) / 1_000_000;

            return [
                'category' => $category,
                'operation' => $operation,
                'iterations' => $iterations,
                'total_ms' => round($totalMs, 3),
                'average_ms' => round($totalMs / $iterations, 4),
                'operations_per_second' => $totalMs > 0 ? round($iterations / ($totalMs / 1000), 2) : 0,
                'status' => 'ok',
            ];
        } catch (Throwable $exception) {
            return [
                'category' => $category,
                'operation' => $operation,
                'iterations' => $iterations,
                'total_ms' => 0,
                'average_ms' => 0,
                'operations_per_second' => 0,
                'status' => 'error: '.$exception->getMessage(),
            ];
        }
    }
}
