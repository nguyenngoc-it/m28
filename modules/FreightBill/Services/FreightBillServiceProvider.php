<?php

namespace Modules\FreightBill\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Modules\FreightBill\Models\FreightBill;
use Modules\FreightBill\Observers\FreightBillObserver;

class FreightBillServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        FreightBill::observe(FreightBillObserver::class);
    }

    public function register()
    {
        $this->app->singleton(FreightBillServiceInterface::class, FreightBillService::class);
    }

    public function provides()
    {
        return [FreightBillServiceInterface::class];
    }
}
