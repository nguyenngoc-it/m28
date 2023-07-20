<?php

namespace Modules\Shopee\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class ShopeeServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(ShopeeServiceInterface::class, function () {
            return new ShopeeService(new ShopeeApi(config('services.shopee')));
        });
    }

    public function provides()
    {
        return [ShopeeServiceInterface::class];
    }
}
