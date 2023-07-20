<?php

namespace Modules\PurchasingOrder\Transformers;

use App\Base\Transformer;
use Modules\OrderExporting\Models\OrderExporting;
use Modules\PurchasingOrder\Models\PurchasingOrder;
use Modules\PurchasingOrder\Models\PurchasingVariant;

class PurchasingOrderTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param PurchasingOrder $purchasingOrder
     * @return array
     */
    public function transform($purchasingOrder)
    {
        return array_merge($purchasingOrder->attributesToArray(), [
            'purchasing_account' => $purchasingOrder->purchasingAccount ? $purchasingOrder->purchasingAccount->only(['username']) : null,
            'purchasing_service' => $purchasingOrder->purchasingService ? $purchasingOrder->purchasingService->only(['name', 'code']) : null,
            'sku_count' => $purchasingOrder->purchasingVariants->count(),
            'sku_matched' => $purchasingOrder->purchasingVariants->filter(function (PurchasingVariant $purchasingVariant) {
                return $purchasingVariant->sku_id;
            })->count(),
        ]);
    }
}
