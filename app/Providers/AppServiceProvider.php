<?php

namespace App\Providers;

use App\Integration\Erp\Contracts\ErpClientInterface;
use App\Integration\Erp\Mock\MockErpClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            ErpClientInterface::class,
            fn () => new MockErpClient(
                (bool) config('integration.erp.simulate_transport_failure', false),
            ),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
