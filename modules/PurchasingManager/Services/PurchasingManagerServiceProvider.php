<?php

namespace Modules\PurchasingManager\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class PurchasingManagerServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(PurchasingManagerServiceInterface::class, PurchasingManagerService::class);
    }

    public function provides()
    {
        return [PurchasingManagerServiceInterface::class];
    }
}
