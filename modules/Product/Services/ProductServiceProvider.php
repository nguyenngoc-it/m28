<?php

namespace Modules\Product\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class ProductServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(ProductServiceInterface::class, ProductService::class);
    }

    public function provides()
    {
        return [ProductServiceInterface::class];
    }
}
