<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Crypto\BlindIndexService;
use App\Services\Crypto\MasterKeyService;
use Illuminate\Support\ServiceProvider;

class CryptoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MasterKeyService::class);
        $this->app->singleton(BlindIndexService::class);
    }

    public function boot(): void
    {
        //
    }
}
