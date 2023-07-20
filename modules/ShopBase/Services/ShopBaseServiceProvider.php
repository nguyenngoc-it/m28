<?php

namespace Modules\ShopBase\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class ShopBaseServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(ShopBaseServiceInterface::class, function () {
            return new ShopBaseService();
        });
    }

    public function provides()
    {
        return [ShopBaseServiceInterface::class];
    }
}
