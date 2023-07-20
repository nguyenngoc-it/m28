<?php

namespace Modules\Sapo\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class SapoServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(SapoServiceInterface::class, function () {
            return new SapoService(new SapoApi(config('services.sapo')));
        });
    }

    public function provides()
    {
        return [SapoServiceInterface::class];
    }
}
