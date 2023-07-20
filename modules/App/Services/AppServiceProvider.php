<?php

namespace Modules\App\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(AppServiceInterface::class, AppService::class);
    }

    public function provides()
    {
        return [AppServiceInterface::class];
    }
}