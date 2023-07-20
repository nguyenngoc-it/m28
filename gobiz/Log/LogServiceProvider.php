<?php

namespace Gobiz\Log;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class LogServiceProvider extends ServiceProvider implements DeferrableProvider
{
    protected $defer = true;

    public function register()
    {
        $this->app->singleton(LoggerFactoryInterface::class, function () {
            return new LoggerFactory(storage_path('logs'));
        });
    }

    public function provides()
    {
        return [
            LoggerFactoryInterface::class,
        ];
    }
}