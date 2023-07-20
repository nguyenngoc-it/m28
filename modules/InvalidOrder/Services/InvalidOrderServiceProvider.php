<?php

namespace Modules\InvalidOrder\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class InvalidOrderServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(InvalidOrderServiceInterface::class, InvalidOrderService::class);
    }

    public function provides()
    {
        return [InvalidOrderServiceInterface::class];
    }
}
