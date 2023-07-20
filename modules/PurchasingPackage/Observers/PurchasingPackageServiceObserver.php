<?php

namespace Modules\PurchasingPackage\Observers;
use Modules\PurchasingPackage\Models\PurchasingPackage;
use Modules\PurchasingPackage\Models\PurchasingPackageService;

class PurchasingPackageServiceObserver
{
    /**
     * @param PurchasingPackage $purchasingPackage
     */
    protected function updatePurchasingPackageServiceAmount(PurchasingPackage $purchasingPackage)
    {
        $serviceAmount = $purchasingPackage->purchasingPackageServices()->sum('amount');
        if($purchasingPackage->service_amount != $serviceAmount) {
            $purchasingPackage->service_amount = $serviceAmount;
                $purchasingPackage->save();
        }
    }

    /**
     * Handle to the PurchasingPackageService "created" event.
     *
     * @param  PurchasingPackageService  $purchasingPackageService
     * @return void
     */
    public function created(PurchasingPackageService $purchasingPackageService)
    {
        if(!empty($purchasingPackageService->amount)) {
            $this->updatePurchasingPackageServiceAmount($purchasingPackageService->purchasingPackage);
        }
    }

    /**
     * Handle the PurchasingPackage "updated" event.
     *
     * @param  PurchasingPackageService $purchasingPackageService
     * @return void
     */
    public function updated(PurchasingPackageService $purchasingPackageService)
    {
        $changed = $purchasingPackageService->getChanges();

        if(isset($changed['amount'])) {
            $this->updatePurchasingPackageServiceAmount($purchasingPackageService->purchasingPackage);
        }
    }
}
