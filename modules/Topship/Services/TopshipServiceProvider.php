<?php

namespace Modules\Topship\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class TopshipServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(TopshipServiceInterface::class, function () {
            return new TopshipService();
        });
    }

    public function provides()
    {
        return [TopshipServiceInterface::class];
    }
}
