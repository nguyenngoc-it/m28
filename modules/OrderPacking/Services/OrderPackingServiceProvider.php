<?php

namespace Modules\OrderPacking\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\OrderPacking\Observers\OrderPackingObserver;

class OrderPackingServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        OrderPacking::observe(OrderPackingObserver::class);
    }

    public function register()
    {
        $this->app->singleton(OrderPackingServiceInterface::class, OrderPackingService::class);
    }

    public function provides()
    {
        return [OrderPackingServiceInterface::class];
    }
}
