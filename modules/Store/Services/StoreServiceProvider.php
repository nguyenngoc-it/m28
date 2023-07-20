<?php

namespace Modules\Store\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class StoreServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(StoreServiceInterface::class, StoreService::class);
    }

    public function provides()
    {
        return [StoreServiceInterface::class];
    }
}
