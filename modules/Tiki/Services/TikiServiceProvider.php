<?php

namespace Modules\Tiki\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class TikiServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(TikiServiceInterface::class, function () {
            return new TikiService(new TikiApi(config('services.tiki')));
        });
    }

    public function provides()
    {
        return [TikiServiceInterface::class];
    }
}
