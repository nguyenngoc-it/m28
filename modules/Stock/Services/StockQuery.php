<?php

namespace Modules\Stock\Services;

use Gobiz\ModelQuery\ModelQuery;
use Gobiz\ModelQuery\ModelQueryFactory;
use Modules\Stock\Models\Stock;

class StockQuery extends ModelQueryFactory
{
    protected $joins = [
        'warehouse_areas' => ['stocks.warehouse_area_id', '=', 'warehouse_areas.id'],
        'warehouses' => ['stocks.warehouse_id', '=', 'warehouses.id'],
        'skus' => ['stocks.sku_id', '=', 'skus.id'],
        'products' => ['skus.product_id', '=', 'products.id'],
        'product_merchants' => ['products.id','=','product_merchants.product_id'],
    ];

    /**
     * Khởi tạo model
     */
    protected function newModel()
    {
        return new Stock();
    }

    /**
     * @param ModelQuery $query
     * @param $id
     */
    protected function applySkuIdFilter(ModelQuery $query, $id)
    {
        if (is_array($id)) {
            $query->getQuery()->whereIn('stocks.sku_id', $id);
        } else {
            $id = trim($id);
            $id = is_numeric($id) ? $id : 0;
            $query->where('stocks.sku_id', $id);
        }
    }

    /**
     * Filter theo kho
     * @param ModelQuery $query
     * @param $warehouseId
     */
    protected function applyWarehouseIdsFilter(ModelQuery $query, $warehouseId)
    {
        if (is_array($warehouseId)) {
            $query->getQuery()->whereIn('stocks.warehouse_id', $warehouseId);
        } else {
            $query->where('documents.stocks', $warehouseId);
        }
    }

    /**
     * @param ModelQuery $query
     * @param $sku_code
     */
    protected function applySkuCodeFilter(ModelQuery $query, $sku_code)
    {
        $query->join('skus')->where('skus.code', trim($sku_code));
    }

    /**
     * @param ModelQuery $query
     * @param array|integer $id
     */
    protected function applySupplierIdFilter(ModelQuery $query, $id)
    {
        $query->join('skus');
        if(is_array($id)) {
            $query->whereIn('skus.supplier_id', $id);
        } else {
            $query->where('skus.supplier_id', $id);
        }
    }


    /**
     * @param ModelQuery $query
     * @param $sku_name
     */
    protected function applySkuNameFilter(ModelQuery $query, $sku_name)
    {
        $query->join('skus')->where('skus.name', 'LIKE', '%' . trim($sku_name) . '%');
    }

    /**
     * @param ModelQuery $query
     * @param $for_delivery_note
     */
    protected function applyForDeliveryNoteFilter(ModelQuery $query, bool $for_delivery_note)
    {
        if ($for_delivery_note) {
            $query->where('stocks.quantity', '>', 0);
        }
    }

    /**
     * Filter theo    thoi gian tao
     *
     * @param ModelQuery $query
     * @param array $input
     */
    protected function applyCreatedAtFilter(ModelQuery $query, array $input)
    {
        $this->applyFilterTimeRange($query, 'stocks.created_at', $input);
    }

    /**
     * @param ModelQuery $query
     * @param $merchantId
     */
    protected function applyMerchantIdFilter(ModelQuery $query, $merchantId)
    {
        $query->join('skus')->join('products')->join('product_merchants')
            ->where('product_merchants.merchant_id', $merchantId);
    }

}
