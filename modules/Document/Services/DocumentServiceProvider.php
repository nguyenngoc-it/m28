<?php

namespace Modules\Document\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class DocumentServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(DocumentServiceInterface::class, DocumentService::class);
    }

    public function provides()
    {
        return [DocumentServiceInterface::class];
    }
}
