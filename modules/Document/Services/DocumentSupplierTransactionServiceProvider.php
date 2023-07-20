<?php

namespace Modules\Document\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class DocumentSupplierTransactionServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(DocumentSupplierTransactionServiceInterface::class, DocumentSupplierTransactionService::class);
    }
}
