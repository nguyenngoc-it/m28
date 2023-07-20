<?php

namespace Gobiz\Bus;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\ServiceProvider;

class BusServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(BusService::PIPELINE, function () {
            return new Pipeline($this->app);
        });
    }

    public function boot()
    {
        Bus::pipeThrough([BusMiddleware::class]);
    }

    public function provides()
    {
        return [BusService::PIPELINE];
    }
}

