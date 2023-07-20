<?php

namespace Modules\Document\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class DocumentDeliveryComparisonServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(DocumentDeliveryComparisonServiceInterface::class, DocumentDeliveryComparisonService::class);
    }
}
