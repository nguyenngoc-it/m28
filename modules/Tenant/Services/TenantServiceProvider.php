<?php

namespace Modules\Tenant\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class TenantServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(TenantServiceInterface::class, TenantService::class);
    }

    public function provides()
    {
        return [TenantServiceInterface::class];
    }
}