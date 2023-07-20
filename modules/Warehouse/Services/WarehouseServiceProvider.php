<?php

namespace Modules\Warehouse\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class WarehouseServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(WarehouseServiceInterface::class, function () {
            return new WarehouseService();
        });
    }

    public function provides()
    {
        return [WarehouseServiceInterface::class];
    }
}
