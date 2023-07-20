<?php

namespace Modules\Order\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderStock;
use Modules\Order\Observers\OrderObserver;
use Modules\Order\Observers\OrderStockObserver;

class OrderServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Order::observe(OrderObserver::class);
        OrderStock::observe(OrderStockObserver::class);
    }

    public function register()
    {
        $this->app->singleton(OrderServiceInterface::class, OrderService::class);
    }

    public function provides()
    {
        return [OrderServiceInterface::class];
    }
}
