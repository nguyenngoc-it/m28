<?php

namespace Modules\PurchasingOrder\Transformers;

use App\Base\Transformer;
use Modules\OrderExporting\Models\OrderExporting;
use Modules\PurchasingOrder\Models\PurchasingOrder;
use Modules\PurchasingOrder\Models\PurchasingVariant;

class PurchasingVariantTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param PurchasingVariant $purchasingVariant
     * @return array
     */
    public function transform($purchasingVariant)
    {
        return array_merge($purchasingVariant->attributesToArray(), [
            'sku' => $purchasingVariant->sku
        ]);
    }
}
