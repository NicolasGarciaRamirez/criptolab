<?php

declare(strict_types=1);

namespace App\Auth;

use App\Services\Crypto\BlindIndexService;
use Illuminate\Auth\Passwords\DatabaseTokenRepository;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use SensitiveParameter;

/**
 * Guarda y busca tokens de recuperación usando email_hash en lugar del
 * correo en claro. La columna se sigue llamando "email" por compatibilidad
 * con el repositorio de Laravel, pero contiene un HMAC-SHA256.
 */
final class BlindIndexTokenRepository extends DatabaseTokenRepository
{
    public function create(CanResetPasswordContract $user)
    {
        $email = $this->blindEmail($user);

        $this->deleteExisting($user);

        $token = $this->createNewToken();

        $this->getTable()->insert($this->getPayload($email, $token));

        return $token;
    }

    protected function deleteExisting(CanResetPasswordContract $user)
    {
        return $this->getTable()->where('email', $this->blindEmail($user))->delete();
    }

    public function exists(CanResetPasswordContract $user, #[SensitiveParameter] $token)
    {
        $record = $this->getTable()->where('email', $this->blindEmail($user))->first();

        return $record
            && ! $this->tokenExpired($record->created_at)
            && $this->hasher->check($token, $record->token);
    }

    public function recentlyCreatedToken(CanResetPasswordContract $user)
    {
        $record = $this->getTable()->where('email', $this->blindEmail($user))->first();

        return $record && $this->tokenRecentlyCreated($record->created_at);
    }

    public function delete(CanResetPasswordContract $user)
    {
        return $this->getTable()->where('email', $this->blindEmail($user))->delete();
    }

    private function blindEmail(CanResetPasswordContract $user): string
    {
        return app(BlindIndexService::class)->hash($user->getEmailForPasswordReset());
    }
}
