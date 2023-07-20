<?php

namespace Modules\ShopBaseUs\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class ShopBaseUsServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(ShopBaseUsServiceInterface::class, function () {
            return new ShopBaseUsService(new ShopBaseUsApi(config('services.shopbaseus')));
        });
    }

    public function provides()
    {
        return [ShopBaseUsServiceInterface::class];
    }
}
