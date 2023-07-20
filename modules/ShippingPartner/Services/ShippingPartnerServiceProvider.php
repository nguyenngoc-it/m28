<?php /** @noinspection PhpMissingReturnTypeInspection */

namespace Modules\ShippingPartner\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Observers\ShippingPartnerObserver;
use Modules\ShippingPartner\Services\ExpectedTransportingPrice\ExpectedTransportingPriceFactory;
use Modules\ShippingPartner\Services\ShippingPartnerApi\ShippingPartnerApiFactory;

class ShippingPartnerServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        ShippingPartner::observe(ShippingPartnerObserver::class);
    }

    public function register()
    {
        $this->app->singleton(ShippingPartnerServiceInterface::class, function () {
            return new ShippingPartnerService(new ShippingPartnerApiFactory(), new ExpectedTransportingPriceFactory());
        });
    }

    public function provides()
    {
        return [ShippingPartnerServiceInterface::class];
    }
}
