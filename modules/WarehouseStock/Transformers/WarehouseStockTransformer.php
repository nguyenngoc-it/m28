<?php

namespace Modules\WarehouseStock\Transformers;


use League\Fractal\TransformerAbstract;
use Modules\Product\Models\BatchOfGood;
use Modules\Warehouse\Transformers\WarehouseTransformerNew;
use Modules\WarehouseStock\Models\WarehouseStock;

class WarehouseStockTransformer extends TransformerAbstract
{

    public function __construct()
    {
        $this->setDefaultIncludes(['warehouse']);
    }

    public function transform(WarehouseStock $warehouseStock)
    {
        $quantityMissing    = $warehouseStock->packing_quantity - $warehouseStock->purchasing_quantity - $warehouseStock->real_quantity;
        $availableInventory = $warehouseStock->real_quantity - $warehouseStock->packing_quantity;

        $skuId = $warehouseStock->sku_id;
        $batchCode = '';
        $batchOfGods = BatchOfGood::where('sku_child_id', $skuId)->first();

        if ($batchOfGods) {
            $batchCode = $batchOfGods->code;
        }

        return [
            'quantity'              => $warehouseStock->quantity,
            'real_quantity'         => $warehouseStock->real_quantity,
            'purchasing_quantity'   => $warehouseStock->purchasing_quantity,
            'packing_quantity'      => $warehouseStock->packing_quantity,
            'saleable_quantity'     => $warehouseStock->saleable_quantity,
            'quantity_missing'      => max($quantityMissing, 0),
            'available_inventory'   => $availableInventory,
            'real_quantity_missing' => $quantityMissing,
            'total_storage_fee'     => round($warehouseStock->sku->storageFeeByWarehouse($warehouseStock->warehouse), 2),
            'batch_code'            => $batchCode
        ];
    }
    /**
     * Include Creator
     *
     * @return \League\Fractal\Resource\Item
     */
    public function includeWarehouse(WarehouseStock $warehouseStock)
    {
        $warehouse = $warehouseStock->warehouse;

        return $this->item($warehouse, new WarehouseTransformerNew);
    }
}
