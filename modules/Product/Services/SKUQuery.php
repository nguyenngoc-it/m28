<?php

namespace Modules\Product\Services;

use Gobiz\ModelQuery\ModelQuery;
use Gobiz\ModelQuery\ModelQueryFactory;
use Illuminate\Database\Eloquent\Builder;
use Modules\Product\Models\Sku;

class SKUQuery extends ModelQueryFactory
{
    protected $joins = [
        'products' => ['skus.product_id', '=', 'products.id'],
        'units' => ['skus.unit_id', '=', 'units.id'],
        'warehouse_stocks' => ['warehouse_stocks.sku_id', '=', 'skus.id'],
    ];

    /**
     * Khởi tạo model
     */
    protected function newModel()
    {
        return new Sku();
    }

    /**
     * @param ModelQuery $query
     * @param $name
     */
    protected function applyNameFilter(ModelQuery $query, $name)
    {
        $query->where('skus.name', 'LIKE', '%' . trim($name) . '%');
    }

    /**
     * @param ModelQuery $query
     * @param $nearlySoldOut
     */
    protected function applyNearlySoldOutFilter(ModelQuery $query, $nearlySoldOut)
    {
        if ($nearlySoldOut) {
            $query->where('skus.status', Sku::STATUS_ON_SELL);
            $query->where('skus.real_stock', '>', 0);
            $query->whereRaw('skus.real_stock <= skus.safety_stock');
        }
    }

    /**
     * @param ModelQuery $query
     * @param $outOfStock
     */
    protected function applyOutOfStockFilter(ModelQuery $query, $outOfStock)
    {
        if ($outOfStock) {
            $query->join('warehouse_stocks')
                ->where('skus.status', Sku::STATUS_ON_SELL)
                ->where('warehouse_stocks.real_quantity', '<=', 0)
                ->groupBy('skus.id');
        }
    }

    /**
     * Thiếu hàng xuất
     * @param ModelQuery $query
     * @param $lackOfExportGoods
     */
    protected function applyLackOfExportGoodsFilter(ModelQuery $query, $lackOfExportGoods)
    {
        if ($lackOfExportGoods) {
            $query->join('warehouse_stocks')
                ->where('skus.status', Sku::STATUS_ON_SELL)
                ->whereRaw('warehouse_stocks.real_quantity + warehouse_stocks.purchasing_quantity < warehouse_stocks.packing_quantity')
                ->groupBy('skus.id');
        }
    }

    /**
     * @param ModelQuery $query
     * @param $notYetInStock
     */
    protected function applyNotYetInStockFilter(ModelQuery $query, $notYetInStock)
    {
        if ($notYetInStock) {
            $query->leftJoin('warehouse_stocks', 'skus.id', '=', 'warehouse_stocks.sku_id')
                ->where('skus.status', Sku::STATUS_ON_SELL)
                ->whereNull('warehouse_stocks.id')
                ->groupBy('skus.id');
        }
    }

    /**
     * @param ModelQuery $query
     * @param $id
     */
    protected function applyIdFilter(ModelQuery $query, $id)
    {
        if (is_array($id)) {
            $query->getQuery()->whereIn('skus.id', $id);
        } else {
            $id = trim($id);
            $id = is_numeric($id) ? $id : 0;
            $query->where('skus.id', $id);
        }
    }

    /**
     * @param ModelQuery $query
     * @param $codes
     */
    protected function applySkuCodesFilter(ModelQuery $query, $codes)
    {
        if (is_array($codes)) {
            $query->getQuery()->whereIn('skus.code', $codes);
        } else {
            $query->where('skus.code', $codes);
        }
    }

    /**
     * @param ModelQuery $query
     * @param $productId
     */
    protected function applyProductIdFilter(ModelQuery $query, $productId)
    {
        if (is_array($productId)) {
            $query->getQuery()->whereIn('skus.product_id', $productId);
        } else {
            $productId = trim($productId);
            $productId = is_numeric($productId) ? $productId : 0;
            $query->where('skus.product_id', trim($productId));
        }
    }

    /**
     * @param ModelQuery $query
     * @param $warehouseId
     */
    protected function applyWarehouseIdFilter(ModelQuery $query, $warehouseId)
    {
        $query->join('warehouse_stocks');
        if (is_array($warehouseId)) {
            $query->getQuery()->whereIn('warehouse_stocks.warehouse_id', $warehouseId);
        } else {
            $query->where('warehouse_stocks.warehouse_id', intval($warehouseId));
        }
        $query->groupBy('skus.id');
    }

    /**
     * @param ModelQuery $query
     * @param $keyword
     */
    protected function applyKeywordFilter(ModelQuery $query, $keyword)
    {
        $keyword = trim($keyword);

        $query->where(function (Builder $q) use ($keyword) {
            $q->where('skus.name', 'LIKE', '%' . $keyword . '%');
            $q->orWhere('skus.code', 'LIKE', '%' . $keyword . '%');
            $q->orWhere('skus.id', $keyword);
            $q->orWhere('skus.barcode', $keyword);
        });
    }

    /**
     * @param ModelQuery $query
     * @param $code
     */
    protected function applyCodeFilter(ModelQuery $query, $code)
    {
        $code = trim($code);
        $query->where('skus.code', 'LIKE', '%' . $code . '%');
    }

    /**
     * @param ModelQuery $query
     * @param $inventory
     */
    protected function applyInventoryFilter(ModelQuery $query, $inventory)
    {
        if ($inventory) {
            $query->where('skus.real_stock', '>', 0);
        } else {
            $query->where('skus.real_stock', '<=', 0);
        }
    }

    /**
     * @param ModelQuery $query
     * @param $product_code
     */
    protected function applyProductCodeFilter(ModelQuery $query, $product_code)
    {
        $query->join('products')
            ->where('products.code', trim($product_code));
    }


    /**
     * @param ModelQuery $query
     * @param $dropship
     */
    protected function applyDropshipFilter(ModelQuery $query, $dropship)
    {
        $query->join('products')
            ->where('products.dropship', $dropship);
    }

    /**
     * @param ModelQuery $query
     * @param $product_name
     */
    protected function applyProductNameFilter(ModelQuery $query, $product_name)
    {
        $query->join('products')
            ->where('products.name', 'LIKE', '%' . trim($product_name) . '%');
    }


    /**
     * Filter theo    thoi gian tao
     *
     * @param ModelQuery $query
     * @param array $input
     */
    protected function applyCreatedAtFilter(ModelQuery $query, array $input)
    {
        $this->applyFilterTimeRange($query, 'skus.created_at', $input);
    }

    /**
     * @param ModelQuery $query
     * @param $merchantIds
     */
    public function applyMerchantIdsFilter(ModelQuery $query, array $merchantIds)
    {
        $query->join('products')->whereIn('products.merchant_id', $merchantIds);
    }

    /**
     * @param ModelQuery $query
     * @param $id
     */
    protected function applySupplierIdFilter(ModelQuery $query, $id)
    {
        if(is_array($id)) {
            $query->whereIn('skus.supplier_id', $id);
        } else {
            $query->where('skus.supplier_id', $id);
        }
    }
}
