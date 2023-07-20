<?php

namespace Modules\Product\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class SkuServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(SkuServiceInterface::class, SkuService::class);
    }

    public function provides()
    {
        return [SkuServiceInterface::class];
    }
}
