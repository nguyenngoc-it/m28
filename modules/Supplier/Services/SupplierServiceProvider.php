<?php

namespace Modules\Supplier\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class SupplierServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(SupplierServiceInterface::class, function () {
            return new SupplierService();
        });
    }

    public function provides()
    {
        return [SupplierServiceInterface::class];
    }
}
