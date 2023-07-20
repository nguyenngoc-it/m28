<?php

namespace Modules\PurchasingPackage\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Modules\PurchasingPackage\Models\PurchasingPackage;
use Modules\PurchasingPackage\Observers\PurchasingPackageObserver;
use Modules\PurchasingPackage\Observers\PurchasingPackageServiceObserver;
use Modules\PurchasingPackage\Observers\PurchasingPackageItemObserver;
use Modules\PurchasingPackage\Models\PurchasingPackageService as PurchasingPackageServiceModel;
use Modules\PurchasingPackage\Models\PurchasingPackageItem;

class PurchasingPackageServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        PurchasingPackageServiceModel::observe(PurchasingPackageServiceObserver::class);
        PurchasingPackageItem::observe(PurchasingPackageItemObserver::class);
        PurchasingPackage::observe(PurchasingPackageObserver::class);
    }

    public function register()
    {
        $this->app->singleton(PurchasingPackageServiceInterface::class, PurchasingPackageService::class);
    }

    public function provides()
    {
        return [PurchasingPackageServiceInterface::class];
    }
}
