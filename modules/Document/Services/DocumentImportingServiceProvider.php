<?php

namespace Modules\Document\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class DocumentImportingServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(DocumentImportingServiceInterface::class, DocumentImportingService::class);
    }

    public function provides()
    {
        return [DocumentImportingServiceInterface::class];
    }
}
