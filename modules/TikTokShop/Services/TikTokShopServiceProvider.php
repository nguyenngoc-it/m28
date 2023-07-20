<?php

namespace Modules\TikTokShop\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class TikTokShopServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(TikTokShopServiceInterface::class, function () {
            return new TikTokShopService(new TikTokShopApi(config('services.tiktokshop')));
        });
    }

    public function provides()
    {
        return [TikTokShopServiceInterface::class];
    }
}
