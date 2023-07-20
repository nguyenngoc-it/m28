<?php

namespace Modules\PurchasingPackage\Transformers;

use League\Fractal\TransformerAbstract;
use Modules\Product\Models\Sku;
use Modules\Product\Transformers\SkuTransformer;
use Modules\PurchasingPackage\Models\PurchasingPackageItem;

class PurchasingPackageItemTransformer extends TransformerAbstract
{
    public function __construct()
    {
        $this->setAvailableIncludes(['sku']);
    }

    public function transform(PurchasingPackageItem $purchasingPackageItem)
    {
        return [
            'id' => $purchasingPackageItem->id,
            'quantity' => $purchasingPackageItem->quantity,
            'sku_id' => $purchasingPackageItem->sku_id,
            'received_quantity' => $purchasingPackageItem->received_quantity,
        ];
    }

    public function includeSku(PurchasingPackageItem $purchasingPackageItem)
    {
        $sku = $purchasingPackageItem->sku;
        if ($sku) {
            return $this->item($sku, new SkuTransformer());
        } else {
            return $this->null();
        }
    }

}
