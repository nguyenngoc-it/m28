<?php

namespace Modules\Document\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class DocumentExportingServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(DocumentExportingServiceInterface::class, DocumentExportingService::class);
    }

    public function provides()
    {
        return [DocumentExportingServiceInterface::class];
    }
}
