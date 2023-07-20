<?php

namespace Modules\Stock\Services;

use Gobiz\ModelQuery\ModelQuery;
use Gobiz\ModelQuery\ModelQueryFactory;
use Modules\Stock\Models\StockLog;

class StockLogQuery extends ModelQueryFactory
{
    protected $joins = [
        'skus' => ['stock_logs.sku_id', '=', 'skus.id'],
    ];

    /**
     * Khởi tạo model
     */
    protected function newModel()
    {
        return new StockLog();
    }


    /**
     * Filter theo sku
     * @param ModelQuery $query
     * @param $skuId
     */
    protected function applySkuIdFilter(ModelQuery $query, $skuId)
    {
        if (is_array($skuId)) {
            $query->getQuery()->whereIn('stock_logs.sku_id', $skuId);
        } else {
            $query->where('stock_logs.sku_id', $skuId);
        }
    }


    /**
     * Filter theo thoi gian tao
     *
     * @param ModelQuery $query
     * @param array $input
     */
    protected function applyCreatedAtFilter(ModelQuery $query, array $input)
    {
        $this->applyFilterTimeRange($query, 'stock_logs.created_at', $input);
    }

    /**
     * Filter theo thoi gian tao
     *
     * @param ModelQuery $query
     * @param array $withoutActions
     */
    protected function applyWithoutActionsFilter(ModelQuery $query, array $withoutActions = [])
    {
        $query->whereNotIn('stock_logs.action', $withoutActions);
    }
}
