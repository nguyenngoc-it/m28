<?php

namespace Modules\OrderExporting\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class OrderExportingServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(OrderExportingServiceInterface::class, OrderExportingService::class);
    }

    public function provides()
    {
        return [OrderExportingServiceInterface::class];
    }
}
