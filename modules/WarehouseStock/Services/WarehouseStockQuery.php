<?php

namespace Modules\WarehouseStock\Services;

use Gobiz\ModelQuery\ModelQuery;
use Gobiz\ModelQuery\ModelQueryFactory;
use Modules\WarehouseStock\Models\WarehouseStock;

class WarehouseStockQuery extends ModelQueryFactory
{
    protected $joins = [
        'warehouses' => ['warehouse_stocks.warehouse_id', '=', 'warehouses.id'],
        'skus' => ['warehouse_stocks.sku_id', '=', 'skus.id'],
        'products' => ['warehouse_stocks.product_id', '=', 'products.id'],
    ];

    /**
     * Khởi tạo model
     */
    protected function newModel()
    {
        return new WarehouseStock();
    }

    /**
     * @param ModelQuery $query
     * @param $keyword
     */
    protected function applyProductKeywordFilter(ModelQuery $query, $keyword)
    {
        $query->join('products')->where(function ($q) use($keyword) {
            $keyword = trim($keyword);
            $q->where('products.code', $keyword);
            $q->orWhere('products.name', 'LIKE', '%' . $keyword . '%');
        });
    }

    /**
     * @param ModelQuery $query
     * @param $product_code
     */
    protected function applyProductCodeFilter(ModelQuery $query, $product_code)
    {
        $query->join('products')->where('products.code', trim($product_code));
    }

    /**
     * @param ModelQuery $query
     * @param $product_name
     */
    protected function applyProductNameFilter(ModelQuery $query, $product_name)
    {
        $query->join('products')->where('products.name', 'LIKE', '%' . trim($product_name) . '%');
    }

    /**
     * @param ModelQuery $query
     * @param $keyword
     */
    protected function applySkuKeywordFilter(ModelQuery $query, $keyword)
    {
        $query->join('skus')->where(function ($q) use($keyword) {
            $keyword = trim($keyword);
            $q->where('skus.code', $keyword);
            $q->orWhere('skus.name', 'LIKE', '%' . $keyword . '%');
        });
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
     * @param $sku_name
     */
    protected function applySkuNameFilter(ModelQuery $query, $sku_name)
    {
        $query->join('skus')->where('skus.name', 'LIKE', '%' . trim($sku_name) . '%');
    }


    /**
     * Filter theo thoi gian tao
     *
     * @param ModelQuery $query
     * @param array $input
     */
    protected function applyCreatedAtFilter(ModelQuery $query, array $input)
    {
        $this->applyFilterTimeRange($query, 'warehouse_stocks.created_at', $input);
    }

    /**
     * Filter theo khoảng số lượng tồn tạm tính
     *
     * @param ModelQuery $query
     * @param array $input
     */
    protected function applyQuantityFilter(ModelQuery $query, array $input)
    {
        $from   = empty($input['from']) ? null : $input['from'];
        $to     = empty($input['to']) ? null : $input['to'];
        $this->applyFilterRange($query, 'warehouse_stocks.quantity', $from, $to);
    }

    /**
     * Filter theo khoảng số lượng tồn tiên lượng
     *
     * @param ModelQuery $query
     * @param array $input
     */
    protected function applySaleableQuantityFilter(ModelQuery $query, array $input)
    {
        $from   = empty($input['from']) ? null : $input['from'];
        $to     = empty($input['to']) ? null : $input['to'];
        $this->applyFilterRange($query, 'warehouse_stocks.saleable_quantity', $from, $to);
    }

}
