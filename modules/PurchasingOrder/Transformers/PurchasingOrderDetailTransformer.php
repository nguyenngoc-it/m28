<?php

namespace Modules\PurchasingOrder\Transformers;

use App\Base\Transformer;
use Illuminate\Support\Collection;
use Modules\PurchasingOrder\Models\PurchasingOrder;
use Modules\PurchasingOrder\Models\PurchasingOrderItem;
use Modules\PurchasingPackage\Models\PurchasingPackage;
use Modules\PurchasingOrder\Models\PurchasingVariant;
use Modules\PurchasingPackage\Models\PurchasingPackageItem;

class PurchasingOrderDetailTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param PurchasingOrder $purchasingOrder
     * @return array
     */
    public function transform($purchasingOrder)
    {
        $purchasingOrderItems = $this->getPurchasingOrderItems($purchasingOrder);
        return array_merge($purchasingOrder->attributesToArray(), [
            'purchasing_account' => $purchasingOrder->purchasingAccount ? $purchasingOrder->purchasingAccount->only(['username']) : null,
            'purchasing_service' => $purchasingOrder->purchasingService ? $purchasingOrder->purchasingService->only(['name', 'code']) : null,
            'sku_count' => $purchasingOrder->purchasingVariants->count(),
            'sku_matched' => $purchasingOrder->purchasingVariants->filter(function (PurchasingVariant $purchasingVariant) {
                return $purchasingVariant->sku_id;
            })->count(),
            'purchasing_order_items' => $purchasingOrderItems,
            'purchasing_packages' => $purchasingOrder->purchasingPackages->map(function (PurchasingPackage $purchasingPackage) {
                $purchasingPackageItem = $purchasingPackage->purchasingPackageItems->first();
                return array_merge($purchasingPackage->attributesToArray(), ['image' => ($purchasingPackageItem && $purchasingPackageItem->purchasingVariant) ? $purchasingPackageItem->purchasingVariant->image : null]);
            })
        ]);
    }

    /**
     * @param PurchasingOrder $purchasingOrder
     * @return \Illuminate\Database\Eloquent\Collection|Collection
     */
    protected function getPurchasingOrderItems(PurchasingOrder $purchasingOrder)
    {
        if ($purchasingOrder->purchasingOrderItems->count()) {
            return $purchasingOrder->purchasingOrderItems->map(function (PurchasingOrderItem $purchasingOrderItem) {
                $purchasingOrderItem->setAttribute('purchasing_variant', $purchasingOrderItem->purchasingVariant);
                return $purchasingOrderItem;
            });
        }

        return $this->getPurchasingOrderItemByPackages($purchasingOrder->purchasingPackages);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Collection $purchasingPackages
     * @return Collection
     */
    protected function getPurchasingOrderItemByPackages(\Illuminate\Database\Eloquent\Collection $purchasingPackages)
    {
        $results = [];
        /** @var PurchasingPackage $purchasingPackage */
        foreach ($purchasingPackages as $purchasingPackage) {
            foreach ($purchasingPackage->purchasingPackageItems as $purchasingPackageItem) {
                $results[$purchasingPackageItem->purchasing_variant_id]['purchasing_order_id']   = $purchasingPackage->purchasing_order_id;
                $results[$purchasingPackageItem->purchasing_variant_id]['purchasing_variant_id'] = $purchasingPackageItem->purchasing_variant_id;
                $results[$purchasingPackageItem->purchasing_variant_id]['received_quantity']     = isset($results[$purchasingPackageItem->purchasing_variant_id]['received_quantity']) ? $results[$purchasingPackageItem->purchasing_variant_id]['received_quantity'] + $purchasingPackageItem->quantity : $purchasingPackageItem->quantity;
                $results[$purchasingPackageItem->purchasing_variant_id]['purchasing_variant']    = $purchasingPackageItem->purchasingVariant;
            }
        }

        return collect($results);
    }
}
