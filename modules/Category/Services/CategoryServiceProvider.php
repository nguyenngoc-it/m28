<?php

namespace Modules\Category\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class CategoryServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(CategoryServiceInterface::class, function () {
            return new CategoryService();
        });
    }

    public function provides()
    {
        return [CategoryServiceInterface::class];
    }
}
