<?php

declare(strict_types=1);

namespace App\Services\Crypto;

use App\Models\CryptoOperation;
use App\Models\User;

final class CryptoOperationService
{
    /**
     * Registra metadatos de la operación. Nunca guarda entradas, salidas,
     * contraseñas ni llaves privadas.
     */
    public function log(
        string $type,
        string $algorithm,
        ?User $user = null,
        string $context = 'lab',
        bool $success = true,
    ): CryptoOperation {
        return CryptoOperation::create([
            'user_id' => ($user ?? auth()->user())?->getKey(),
            'type' => $type,
            'algorithm' => $algorithm,
            'context' => $context,
            'success' => $success,
        ]);
    }
}
