<?php

namespace Modules\OrderExporting\Transformers;

use App\Base\Transformer;
use Modules\OrderExporting\Models\OrderExportingItem;

class OrderExportingItemTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param OrderExportingItem $orderExportingItem
     * @return mixed
     */
    public function transform($orderExportingItem)
    {
        return array_merge($orderExportingItem->attributesToArray(), [
            'sku' => $orderExportingItem->sku ? $orderExportingItem->sku->only(['code','name','product_id']) : null,
            'sku_name' => $orderExportingItem->sku ? $orderExportingItem->sku->name : null,
            'sku_code' => $orderExportingItem->sku ? $orderExportingItem->sku->code : null,
        ]);
    }
}
