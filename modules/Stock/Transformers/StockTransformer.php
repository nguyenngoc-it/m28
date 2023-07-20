<?php

namespace Modules\Stock\Transformers;

use Illuminate\Support\Facades\DB;
use League\Fractal\TransformerAbstract;
use Modules\Merchant\Models\Merchant;
use Modules\Merchant\Transformers\MerchantTransformer;
use Modules\Product\Models\BatchOfGood;
use Modules\Product\Models\Sku;
use Modules\Product\Transformers\SkuTransformer;
use Modules\Stock\Models\Stock;
use Modules\Warehouse\Models\Warehouse;
use Modules\Warehouse\Models\WarehouseArea;
use Modules\Warehouse\Transformers\WarehouseAreaTransformer;
use Modules\Warehouse\Transformers\WarehouseTransformerNew;

class StockTransformer extends TransformerAbstract
{
    public function __construct()
    {
        $this->setAvailableIncludes(['sku', 'merchant', 'merchants', 'warehouse', 'warehouse_area']);
    }

    public function transform(Stock $stock, $fromRoot = false)
    {
        $realQuantity    = $stock->real_quantity;
        $quantity        = $stock->quantity;
        $totalStorageFee = $stock->total_storage_fee;
        $isParentSku     = false;

        if ($fromRoot) {
            $stockCh = Stock::select(DB::raw('SUM(stocks.real_quantity) as real_quantity, SUM(stocks.quantity) as quantity, SUM(stocks.total_storage_fee) as total_storage_fee'))
                        ->where('stocks.sku_id', $stock->sku_id)
                        ->first();

            $realQuantity    = $stockCh->real_quantity;
            $quantity        = $stockCh->quantity;
            $totalStorageFee = $stockCh->total_storage_fee;
        }

        // Kiểm tra xem Sku này có phải là Sku cha hay không
        $skuChildrens = $stock->sku->skuChildren;
        if (count($skuChildrens) > 0) {
            $realQuantity    = 0;
            $quantity        = 0;
            $totalStorageFee = 0;
            $isParentSku     = true;
            foreach ($skuChildrens as $skuChildren) {
                $stocks = $skuChildren->stocks;
                if ($stocks) {
                    foreach ($stocks as $stock) {
                        $realQuantity    += $stock->real_quantity;
                        $quantity        += $stock->quantity;
                        $totalStorageFee += $stock->total_storage_fee;
                    }
                }
            }
        }

        $skuId = $stock->sku_id;
        $batchCode = '';
        $batchOfGods = BatchOfGood::where('sku_child_id', $skuId)->first();

        if ($batchOfGods) {
            $batchCode = $batchOfGods->code;
        }
        
        return [
            'is_parent_sku'     => $isParentSku,
            'real_quantity'     => $realQuantity,
            'quantity'          => $quantity,
            'total_storage_fee' => $totalStorageFee,
            'batch_code'        => $batchCode
        ];
    }

    /**
     * Include merchant
     *
     * @return \League\Fractal\Resource\Item
     */
    public function includeMerchant(Stock $stock)
    {
        $sku        = $stock->sku;
        $merchantId = $sku->merchant_id;
        $merchant   = Merchant::find($merchantId);
        if (!$merchant) {
            $merchant = new Merchant();
        }
        return $this->item($merchant, new MerchantTransformer());
    }

    /**
     * Include merchant
     *
     * @return \League\Fractal\Resource\Item
     */
    public function includeMerchants(Stock $stock)
    {
        $merchants  = $stock->sku->product->merchants;

        if ($merchants) {
            return $this->collection($merchants, new MerchantTransformer());
        } else {
            return $this->null();
        }
    }

    /** include warehouse
     * @param Stock $stock
     * @return \League\Fractal\Resource\Item
     */
    public function includeWarehouse(Stock $stock)
    {
        $warehouse = $stock->warehouse;
        if (!$warehouse) {
            $warehouse = new Warehouse();
        }
        return $this->item($warehouse, new WarehouseTransformerNew());
    }

    /** include sku
     * @param Stock $stock
     * @return \League\Fractal\Resource\Item
     */
    public function includeSku(Stock $stock)
    {
        $sku = $stock->sku;
        if (!$sku) {
            $sku = new Sku();
        }
        return $this->item($sku, new  SkuTransformer());
    }

    /** include warehouse area
     * @param Stock $stock
     * @return \League\Fractal\Resource\Item
     */
    public function includeWarehouseArea(Stock $stock)
    {
        $warehouseArea = $stock->warehouseArea;
        if (!$warehouseArea) {
            $warehouseArea = new WarehouseArea();
        }
        return $this->item($warehouseArea, new WarehouseAreaTransformer());
    }
}
