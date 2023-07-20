<?php

namespace Modules\WarehouseStock\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class WarehouseStockServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(WarehouseStockServiceInterface::class, WarehouseStockService::class);
    }

    public function provides()
    {
        return [WarehouseStockServiceInterface::class];
    }
}