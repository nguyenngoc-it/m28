<?php

namespace Modules\Stock\Transformers;

use App\Base\Transformer;
use Modules\Product\Models\Sku;
use Modules\Stock\Models\Stock;

class StockDetailTransformer extends Transformer
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

        return compact('stock', 'category', 'sku', 'warehouse', 'warehouseArea');
    }
}
