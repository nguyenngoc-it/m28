<?php

namespace Modules\Product\Transformers;

use App\Base\Transformer;
use Modules\Product\Models\Sku;
use Modules\WarehouseStock\Models\WarehouseStock;

class SkuListItemTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param Sku $sku
     * @return mixed
     */
    public function transform($sku)
    {
        $category        = $sku->category;
        $unit            = $sku->unit;
        $prices          = $sku->prices;
        $warehouse_stocks = $sku->warehouseStocks->transform(function (WarehouseStock $warehouseStock) {
           return array_merge($warehouseStock->warehouse->only(['name']), $warehouseStock->only(['quantity', 'real_quantity', 'purchasing_quantity', 'packing_quantity', 'saleable_quantity']));
        });
        return compact('sku', 'category', 'unit', 'prices', 'warehouse_stocks');
    }
}
