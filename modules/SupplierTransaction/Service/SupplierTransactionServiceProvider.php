<?php

namespace Modules\SupplierTransaction\Service;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class SupplierTransactionServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(SupplierTransactionInterface::class, function (){
            return new SupplierTransactionService();
        });
    }

    public function provider()
    {
        return [SupplierTransactionInterface::class];
    }
}
