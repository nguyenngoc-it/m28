<?php

namespace Modules\Lazada\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class LazadaServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(LazadaServiceInterface::class, function () {
            return new LazadaService(new LazadaApi(config('services.lazada')));
        });
    }

    public function provides()
    {
        return [LazadaServiceInterface::class];
    }
}
