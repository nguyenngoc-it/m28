<?php

namespace Modules\Document\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class DocumentPackingServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(DocumentPackingServiceInterface::class, DocumentPackingService::class);
    }

    public function provides()
    {
        return [DocumentPackingServiceInterface::class];
    }
}
