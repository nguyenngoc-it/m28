<?php

namespace Modules\Stock\Transformers;

use App\Base\Transformer;
use Modules\Product\Models\Sku;
use Modules\Stock\Models\Stock;

class StockListItemTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param Stock $stock
     * @return mixed
     */
    public function transform($stock)
    {
        $sku           = $stock->sku;
        $category      = ($sku instanceof Sku) ? $sku->category : null;
        $warehouse     = $stock->warehouse;
        $warehouseArea = $stock->warehouseArea;
        $sellers       = ($sku instanceof Sku && $sku->product) ? $sku->product->merchants : [];

        return [
            'stock' => $stock->attributesToArray(),
            'sku' => $sku,
            'category' => $category,
            'warehouse' => $warehouse,
            'currency' => $warehouse->country->currency,
            'warehouse_area' => $warehouseArea,
            'sellers' => $sellers
        ];
    }
}
