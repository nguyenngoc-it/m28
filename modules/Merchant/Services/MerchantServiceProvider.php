<?php

namespace Modules\Merchant\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class MerchantServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(MerchantServiceInterface::class, function () {
            return new MerchantService();
        });
    }

    public function provides()
    {
        return [MerchantServiceInterface::class];
    }
}
