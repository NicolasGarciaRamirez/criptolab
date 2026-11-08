<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Services\Audit\AuditService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Events\Dispatcher;

final class AuditAuthSubscriber
{
    public function __construct(private readonly AuditService $audit)
    {
    }

    public function onLogin(Login $event): void
    {
        $this->audit->log($event->user, 'login', 'Inicio de sesión');
    }

    public function onLogout(Logout $event): void
    {
        $this->audit->log($event->user, 'logout', 'Cierre de sesión');
    }

    public function onFailed(Failed $event): void
    {
        // No se guardan las credenciales intentadas.
        $this->audit->log($event->user, 'login_failed', 'Intento de inicio de sesión fallido', [
            'guard' => $event->guard,
        ]);
    }

    public function onPasswordReset(PasswordReset $event): void
    {
        $this->audit->log($event->user, 'password_reset', 'Contraseña restablecida');
    }

    public function onRegistered(Registered $event): void
    {
        $this->audit->log($event->user, 'registered', 'Cuenta registrada');
    }

    public function onVerified(Verified $event): void
    {
        $this->audit->log($event->user, 'email_verified', 'Correo electrónico verificado');
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(Login::class, [self::class, 'onLogin']);
        $events->listen(Logout::class, [self::class, 'onLogout']);
        $events->listen(Failed::class, [self::class, 'onFailed']);
        $events->listen(PasswordReset::class, [self::class, 'onPasswordReset']);
        $events->listen(Registered::class, [self::class, 'onRegistered']);
        $events->listen(Verified::class, [self::class, 'onVerified']);
    }
}
