<?php

namespace Modules\Transaction\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class TransactionServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(TransactionServiceInterface::class, TransactionService::class);
    }

    public function provides()
    {
        return [TransactionServiceInterface::class];
    }
}
