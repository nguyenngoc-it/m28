<?php

namespace App\Services\Log;

use Illuminate\Support\ServiceProvider;

class LogServiceProvider extends ServiceProvider
{
    protected $defer = true;

    public function register()
    {
        $this->app->singleton(LogServiceInterface::class, LogService::class);
    }

    public function provides()
    {
        return [
            LogServiceInterface::class,
        ];
    }
}