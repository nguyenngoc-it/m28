<?php

namespace Modules\Marketplace\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class MarketplaceServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(MarketplaceServiceInterface::class, function () {
            return new MarketplaceService($this->makeMarketplaces());
        });
    }

    /**
     * @return array
     */
    public function provides()
    {
        return [MarketplaceServiceInterface::class];
    }

    /**
     * @return MarketplaceInterface[]
     */
    protected function makeMarketplaces()
    {
        return array_map(function ($class) {
            return $this->app->make($class);
        }, config('marketplace.marketplaces'));
    }
}
