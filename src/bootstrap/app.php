<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
        ]);

        $middleware->append(SecurityHeaders::class);

        // Los datos criptográficos deben conservarse byte a byte.
        $middleware->trimStrings(except: [
            'plaintext',
            'text',
            'message',
            'key',
            'hash',
            'signature',
            'payload',
            'public_key',
            'private_key',
            'salt',
            'passphrase',
            'expected',
            'ciphertext',
            'aad',
            'value_a',
            'value_b',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash([
            'password',
            'password_confirmation',
            'password_plain',
            'private_key',
            'key',
            'passphrase',
            'signing_private_key',
            'encryption_private_key',
            'certificate_private_key',
        ]);

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
