<?php

declare(strict_types=1);

namespace App\Services\Crypto;

use App\Exceptions\CryptoException;
use SensitiveParameter;

final class TimingComparisonService
{
    /**
     * @return array{iterations: int, standard_ms: float, hash_equals_ms: float, difference_ms: float, same: bool, note: string}
     */
    public function compare(
        #[SensitiveParameter] string $first,
        #[SensitiveParameter] string $second,
        int $iterations = 50000,
    ): array {
        if ($first === '' || $second === '') {
            throw new CryptoException('Los dos valores de prueba son obligatorios.');
        }

        if (strlen($first) !== strlen($second)) {
            throw new CryptoException('Para la comparación de tiempos usa valores de la misma longitud.');
        }

        $iterations = max(100, min(1_000_000, $iterations));

        $standardStart = hrtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $result = $first === $second;
        }
        $standardMs = (hrtime(true) - $standardStart) / 1_000_000;

        $safeStart = hrtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $result = hash_equals($first, $second);
        }
        $safeMs = (hrtime(true) - $safeStart) / 1_000_000;

        return [
            'iterations' => $iterations,
            'standard_ms' => round($standardMs, 3),
            'hash_equals_ms' => round($safeMs, 3),
            'difference_ms' => round($safeMs - $standardMs, 3),
            'same' => $first === $second,
            'note' => 'Los tiempos son orientativos. La lección es que === puede detenerse en la primera diferencia; hash_equals compara de forma constante.',
        ];
    }
}
