<?php

namespace Modules\Document\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class DocumentSkuInventoryServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(DocumentSkuInventoryServiceInterface::class, DocumentSkuInventoryService::class);
    }

    public function provides()
    {
        return [DocumentExportingServiceInterface::class];
    }
}
