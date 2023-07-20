<?php

namespace Modules\Stock\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Modules\Stock\Models\Stock;
use Modules\Stock\Observers\StockObserver;

class StockServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function boot()
    {
        Stock::observe(StockObserver::class);
    }

    public function register()
    {
        $this->app->singleton(StockServiceInterface::class, StockService::class);
    }

    public function provides()
    {
        return [StockServiceInterface::class];
    }
}
