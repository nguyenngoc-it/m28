<?php

namespace Gobiz\Setting;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class SettingServiceProvider extends ServiceProvider implements DeferrableProvider
{
    protected $defer = true;

    public function register()
    {
        $this->app->singleton(SettingRepositoryInterface::class, SettingRepository::class);
    }

    public function provides()
    {
        return [SettingRepositoryInterface::class];
    }
}