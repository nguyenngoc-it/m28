<?php

namespace Modules\Document\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class DocumentFreightBillInventoryServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(DocumentFreightBillInventoryServiceInterface::class, DocumentFreightBillInventoryService::class);
    }
}
