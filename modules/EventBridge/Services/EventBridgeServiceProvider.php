<?php

namespace Modules\EventBridge\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class EventBridgeServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(EventBridgeServiceInterface::class, EventBridgeService::class);
    }

    public function provides()
    {
        return [EventBridgeServiceInterface::class];
    }
}
