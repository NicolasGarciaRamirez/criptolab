<?php

declare(strict_types=1);

namespace App\Auth;

use App\Services\Crypto\BlindIndexService;
use Illuminate\Auth\EloquentUserProvider;
use SensitiveParameter;

/**
 * Provider de Eloquent que traduce el email en claro a su índice ciego.
 * Laravel sigue usando el atributo "email" en los flujos de recuperación,
 * pero la consulta real se hace contra email_hash.
 */
final class BlindIndexUserProvider extends EloquentUserProvider
{
    public function retrieveByCredentials(#[SensitiveParameter] array $credentials)
    {
        if (isset($credentials['email'])) {
            $credentials['email_hash'] = app(BlindIndexService::class)
                ->hash((string) $credentials['email']);
            unset($credentials['email']);
        }

        return parent::retrieveByCredentials($credentials);
    }
}
