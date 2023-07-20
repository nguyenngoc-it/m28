<?php

namespace Modules\WarehouseStock\Transformers;

use App\Base\Transformer;
use Modules\WarehouseStock\Models\WarehouseStock;

class WarehouseStockListItemTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param WarehouseStock $warehouseStock
     * @return mixed
     */
    public function transform($warehouseStock)
    {
        $sku       = $warehouseStock->sku;
        $product   = $warehouseStock->product;
        $warehouse = $warehouseStock->warehouse;

        return array('warehouse_stock' => $warehouseStock,
            'product' => $product,
            'sku' => $sku,
            'warehouse' => $warehouse
        );
    }
}
