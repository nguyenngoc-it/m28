<?php

namespace Modules\Location\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class LocationServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(LocationServiceInterface::class, function () {
            return new LocationService();
        });
    }

    public function provides()
    {
        return [LocationServiceInterface::class];
    }
}
