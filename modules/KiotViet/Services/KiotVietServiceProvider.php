<?php

namespace Modules\KiotViet\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class KiotVietServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(KiotVietServiceInterface::class, function () {
            return new KiotVietService(new KiotVietApi(config('services.kiotviet')));
        });
    }

    public function provides()
    {
        return [KiotVietServiceInterface::class];
    }
}
