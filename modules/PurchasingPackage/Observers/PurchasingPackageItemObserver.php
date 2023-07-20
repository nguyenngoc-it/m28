<?php

namespace Modules\PurchasingPackage\Observers;

use Modules\PurchasingPackage\Models\PurchasingPackageItem;
use Modules\WarehouseStock\Jobs\CalculateWarehouseStockJob;

class PurchasingPackageItemObserver
{
    public $afterCommit = true;

    /**
     * @param PurchasingPackageItem $purchasingPackageItem
     */
    protected function calculateWarehouseStockJob(PurchasingPackageItem $purchasingPackageItem)
    {
        $purchasingPackage = $purchasingPackageItem->purchasingPackage;
        if ($purchasingPackage->destination_warehouse_id && $purchasingPackageItem->sku_id) {
            dispatch(new CalculateWarehouseStockJob($purchasingPackageItem->sku_id, $purchasingPackage->destination_warehouse_id));
        }
    }

    /**
     * @param PurchasingPackageItem $purchasingPackageItem
     */
    public function created(PurchasingPackageItem $purchasingPackageItem)
    {
        $this->calculateWarehouseStockJob($purchasingPackageItem);
    }

    /**
     * @param PurchasingPackageItem $purchasingPackageItem
     */
    public function updated(PurchasingPackageItem $purchasingPackageItem)
    {
        $changed = $purchasingPackageItem->getChanges();
        if (isset($changed['quantity'])) {
            $this->calculateWarehouseStockJob($purchasingPackageItem);
        }
    }
}
