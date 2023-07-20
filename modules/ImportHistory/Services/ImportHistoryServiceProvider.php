<?php

namespace Modules\ImportHistory\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class ImportHistoryServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(ImportHistoryServiceInterface::class, ImportHistoryService::class);
    }

    public function provides()
    {
        return [ImportHistoryServiceInterface::class];
    }
}