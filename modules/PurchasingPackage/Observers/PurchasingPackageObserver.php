<?php

namespace Modules\PurchasingPackage\Observers;

use Modules\PurchasingPackage\Models\PurchasingPackage;
use Modules\WarehouseStock\Jobs\CalculateWarehouseStockJob;

class PurchasingPackageObserver
{
    public $afterCommit = true;

    /**
     * @param PurchasingPackage $purchasingPackage
     */
    protected function calculateWarehouseStockJob(PurchasingPackage $purchasingPackage)
    {
        if ($purchasingPackage->destination_warehouse_id) {
            foreach ($purchasingPackage->purchasingPackageItems as $purchasingPackageItem) {
                if ($purchasingPackageItem->sku_id) {
                    dispatch(new CalculateWarehouseStockJob($purchasingPackageItem->sku_id, $purchasingPackage->destination_warehouse_id));
                }
            }
        }
    }

    /**
     * @param PurchasingPackage $purchasingPackage
     */
    public function updated(PurchasingPackage $purchasingPackage)
    {
        $changed = $purchasingPackage->getChanges();
        if (isset($changed['status']) && $changed['status'] == PurchasingPackage::STATUS_CANCELED) {
            $this->calculateWarehouseStockJob($purchasingPackage);
        }
    }
}
