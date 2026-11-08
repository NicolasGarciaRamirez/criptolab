<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->extend('auth.password', function ($manager, $app) {
            return new \App\Auth\BlindIndexPasswordBrokerManager($app);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Auth::provider('blind-index', function ($app, array $config) {
            return new \App\Auth\BlindIndexUserProvider($app['hash'], $config['model']);
        });

        \Illuminate\Support\Facades\Event::subscribe(\App\Listeners\AuditAuthSubscriber::class);

        if ($this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}
