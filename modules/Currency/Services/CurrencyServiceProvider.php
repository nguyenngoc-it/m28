<?php

namespace Modules\Currency\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class CurrencyServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(CurrencyServiceInterface::class, function () {
            return new CurrencyService();
        });
    }

    public function provides()
    {
        return [CurrencyServiceInterface::class];
    }
}
