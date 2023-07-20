<?php

namespace Modules\PurchasingOrder\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class PurchasingOrderServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(PurchasingOrderServiceInterface::class, PurchasingOrderService::class);
    }

    public function provides()
    {
        return [PurchasingOrderServiceInterface::class];
    }
}
