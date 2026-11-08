<?php

namespace App\Providers;

use App\Auth\BlindIndexPasswordBrokerManager;
use App\Auth\BlindIndexUserProvider;
use App\Listeners\AuditAuthSubscriber;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->extend('auth.password', function ($manager, $app) {
            return new BlindIndexPasswordBrokerManager($app);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::provider('blind-index', function ($app, array $config) {
            return new BlindIndexUserProvider($app['hash'], $config['model']);
        });

        Event::subscribe(AuditAuthSubscriber::class);

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
