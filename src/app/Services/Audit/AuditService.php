<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\User;

final class AuditService
{
    /**
     * Registra una acción relevante sin guardar contraseñas, llaves ni
     * textos claros. El metadata se filtra por seguridad.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function log(
        ?User $user,
        string $action,
        ?string $description = null,
        array $metadata = [],
    ): AuditLog {
        $request = app()->bound('request') ? request() : null;

        return AuditLog::create([
            'user_id' => $user?->getKey(),
            'action' => $action,
            'description' => $description,
            'metadata' => $this->sanitize($metadata),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function sanitize(array $metadata): array
    {
        $blocked = ['password', 'password_confirmation', 'password_plain', 'private_key', 'key', 'secret', 'token'];

        foreach ($metadata as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), $blocked, true)) {
                unset($metadata[$key]);

                continue;
            }

            if (is_array($value)) {
                $metadata[$key] = $this->sanitize($value);
            }
        }

        return $metadata;
    }
}
