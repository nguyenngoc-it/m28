<?php

namespace Modules\Service\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class ServiceServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(ServiceServiceInterface::class, ServiceService::class);
    }

    public function provides()
    {
        return [ServiceServiceInterface::class];
    }
}
