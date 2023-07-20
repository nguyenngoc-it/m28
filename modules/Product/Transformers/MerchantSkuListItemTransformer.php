<?php

namespace Modules\Product\Transformers;

use App\Base\Transformer;
use Modules\Product\Models\Sku;
use Modules\Store\Models\StoreSku;
use Modules\WarehouseStock\Models\WarehouseStock;

class MerchantSkuListItemTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param Sku $sku
     * @param array $warehouseId
     * @return mixed
     */
    public function transform($sku, array $warehouseId = [])
    {
        $category           = $sku->category;
        $unit               = $sku->unit;
        $prices             = $sku->prices;
        $product            = $sku->product->only(['id', 'name', 'code', 'images']);
        $skuWarehouseStocks = $warehouseId ? $sku->warehouseStocks->whereIn('warehouse_id', $warehouseId) : $sku->warehouseStocks;
        $warehouse_stocks   = $skuWarehouseStocks->transform(function (WarehouseStock $warehouseStock) use ($sku) {
            $quantityMissing = $warehouseStock->packing_quantity - $warehouseStock->purchasing_quantity - $warehouseStock->real_quantity;
            $warehouse       = $warehouseStock->warehouse;
            return array_merge(
                $warehouse->only(['id', 'name']),
                ['warehouse_status' => $warehouse->status],
                $warehouseStock->only(['quantity', 'real_quantity', 'purchasing_quantity', 'packing_quantity', 'saleable_quantity']),
                ['total_storage_fee' => round($sku->storageFeeByWarehouse($warehouseStock->warehouse), 2)],
                [
                    'available_inventory' => $warehouseStock->real_quantity - $warehouseStock->packing_quantity,
                    'quantityMissing' => max($quantityMissing, 0),
                    'real_quantity_missing' => $quantityMissing
                ]
            );
        });
        $services           = $sku->product->services;
        $storeSkus          = StoreSku::query()->where(['sku_id' => $sku->id])->get();
        $sellChannels       = [];
        /** @var StoreSku $storeSku */
        foreach ($storeSkus as $storeSku) {
            $sellChannels[$storeSku->marketplace_code]['channel']  = $storeSku->marketplace_code;
            $sellChannels[$storeSku->marketplace_code]['stores'][] = $storeSku->store ? $storeSku->store->name : $storeSku->marketplace_store_id;
        }
        $sku->setSellChannel(array_values($sellChannels));

        return compact('sku', 'category', 'unit', 'prices', 'warehouse_stocks', 'product', 'services');
    }
}
