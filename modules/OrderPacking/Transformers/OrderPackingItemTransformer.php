<?php

namespace Modules\OrderPacking\Transformers;

use App\Base\Transformer;
use Modules\OrderPacking\Models\OrderPackingItem;

class OrderPackingItemTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param OrderPackingItem $orderPackingItem
     * @return mixed
     */
    public function transform($orderPackingItem)
    {
        return array_merge($orderPackingItem->attributesToArray(), [
            'sku' => $orderPackingItem->sku ? $orderPackingItem->sku->only(['code', 'name', 'product_id']) : null,
            'sku_name' => $orderPackingItem->sku ? $orderPackingItem->sku->name : null,
            'sku_code' => $orderPackingItem->sku ? $orderPackingItem->sku->code : null,
            'warehouse_area' => $orderPackingItem->warehouseArea ? $orderPackingItem->warehouseArea->name : null
        ]);
    }
}
