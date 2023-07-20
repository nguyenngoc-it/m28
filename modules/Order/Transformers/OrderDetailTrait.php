<?php

namespace Modules\Order\Transformers;

use Illuminate\Support\Collection;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderSku;
use Modules\Order\Models\OrderStock;
use Modules\Product\Models\Sku;
use Modules\Product\Models\SkuCombo;
use Modules\Stock\Models\Stock;
use Modules\Warehouse\Models\Warehouse;
use Modules\Warehouse\Models\WarehouseArea;
use Modules\Document\Transformers\DocumentTransformer;
use Modules\Order\Models\OrderSkuCombo;

trait OrderDetailTrait
{
    /**
     * @param Order $order
     * @return array|\Illuminate\Database\Eloquent\Collection|Collection
     */
    protected function makeOrderStocks(Order $order)
    {
        $orderStockData = $order->orderStocks()->get();
        return ($orderStockData) ? $orderStockData->map(function (OrderStock $orderStock) {
            $sku     = $orderStock->sku;
            $product = $sku->product;
            return array_merge(
                ['orderStock' => $orderStock, 'sku' => $sku, 'product' => $product],
                $orderStock->only(['warehouse', 'warehouseArea'])
            );
        }) : [];
    }

    /**
     * @param Order $order
     * @return array
     */
    protected function makeOrderSkus(Order $order , $isOperation = false)
    {
        $orderSkuData = $order->orderSkus();

        if (!$isOperation) {
            $orderSkuData = $orderSkuData->where('from_sku_combo', OrderSku::FROM_SKU_COMBO_FALSE);
        }

        $orderSkuData = $orderSkuData->with(['sku.product'])->get();
        
        $orderSkus    = [];
        /** @var OrderSku $orderSku */
        foreach ($orderSkuData as $orderSku) {
            $sku = $orderSku->sku;
            if (!$sku instanceof Sku) continue;
            $product      = $sku->product;
            $unit         = $sku->unit;
            $stocks_query = $sku->stocks()
                ->select(['stocks.*'])
                ->join('warehouse_areas', 'warehouse_areas.id', '=', 'stocks.warehouse_area_id');
            if ($order->warehouse_id) {
                $stocks_query->where('stocks.warehouse_id', $order->warehouse_id);
            }

            if ($product["dropship"] === 0)
                $stocks_query->where('stocks.quantity', '>', 0);

            $stocks = $stocks_query->get();

            $stocks = $stocks->map(function (Stock $stock) {
                return array_merge(['stock' => $stock], $stock->only(['warehouse', 'warehouseArea']));
            });

            $warehouses = [];
            foreach ($stocks as $stock) {
                $warehouse     = $stock['warehouse'];
                $warehouseArea = $stock['warehouseArea'];

                if (!$warehouse instanceof Warehouse || !$warehouseArea instanceof WarehouseArea) {
                    continue;
                }

                if (!isset($warehouses[$warehouse->id])) {
                    $warehouses[$warehouse->id] = [
                        'warehouse' => $warehouse,
                        'warehouseAreas' => [['warehouseArea' => $warehouseArea, 'stock' => $stock['stock']]]
                    ];
                } else {
                    $warehouses[$warehouse->id]['warehouseAreas'][] = ['warehouseArea' => $warehouseArea, 'stock' => $stock['stock']];
                }
            }

            $orderSkus[] = [
                'orderSku' => $orderSku,
                'sku' => $sku,
                'product' => $product,
                'unit' => $unit,
                'stocks' => $stocks,
                'warehouses' => array_values($warehouses)
            ];
        }

        return $orderSkus;
    }

    /**
     * @param Order $order
     * @return \Illuminate\Database\Eloquent\Collection|Collection
     */
    protected function makeDocuments(Order $order)
    {
        return $order->documents()->orderBy('id', 'desc')->get()->map(function ($document) {
            return (new DocumentTransformer())->transform($document);
        })->filter()->values();
    }

    /**
     * @param Order $order
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function makeOrderFreightBills(Order $order)
    {
        return $order->freightBills;
    }

    /**
     * @param Order $order
     * @return mixed
     */
    protected function makeOrderSkuCombos(Order $order)
    {
        return $order->orderSkuCombos->map(function (OrderSkuCombo $orderSkuCombo) use ($order){
            $orderSkus                   = $order->orderSkus;
            $skuCombo                    = $orderSkuCombo->skuCombo;
            $orderSkuCombo->total_amount = (int) $orderSkuCombo->quantity * (double) $orderSkuCombo->price;
            $orderSkuCombo->skuCombo     = $orderSkuCombo->skuCombo;

            $orderSkuComboSkus = $order->orderSkuComboSkus;
            $dataOrderSkus = [];
            if ($orderSkuComboSkus) {
                foreach ($orderSkuComboSkus as $orderSkuComboSku) {
                    $orderSkuComboSku->sku = $orderSkuComboSku->sku;
                    $skuComboId = $orderSkuComboSku->sku_combo_id;
                    $skuId = $orderSkuComboSku->sku_id;
                    if ($skuCombo->id == $skuComboId) {
                        $dataOrderSkus[$skuId] = $orderSkuComboSku;
                    }
                }
            }

            // dd(array_values($dataOrderSkus));
            return [
                'order_skus'      => array_values($dataOrderSkus),
                'order_sku_combo' => $orderSkuCombo,
                // 'sku_combo'       => $skuCombo,
                // 'sku' => $skuCombo->skus->map(function (Sku $sku){
                //     return $sku;
                // })
            ];
        });
    }
}
