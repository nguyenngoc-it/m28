<?php

namespace Modules\Locking\Services;

use Illuminate\Support\ServiceProvider;

class LockingServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(LockingServiceInterface::class, function () {
           return new LockingService();
        });
    }
}