<?php

namespace Modules\Service\Services;

use Gobiz\ModelQuery\ModelQuery;
use Gobiz\ModelQuery\ModelQueryFactory;
use Modules\Service\Models\StorageFeeSkuStatistic;

class StorageFeeSkuStatisticQuery extends ModelQueryFactory
{
    protected $joins = [
    ];

    /**
     * Khởi tạo model
     */
    protected function newModel()
    {
        return new StorageFeeSkuStatistic();
    }

    /**
     * @param ModelQuery $query
     * @param $id
     */
    protected function applyStockIdFilter(ModelQuery $query, $id)
    {
        if (is_array($id)) {
            $query->getQuery()->whereIn('storage_fee_sku_statistics.stock_id', $id);
        } else {
            $id = trim($id);
            $id = is_numeric($id) ? $id : 0;
            $query->where('storage_fee_sku_statistics.stock_id', $id);
        }
    }

    /**
     * Filter theo    thoi gian tao
     *
     * @param ModelQuery $query
     * @param array $input
     */
    protected function applyClosingTimeFilter(ModelQuery $query, array $input)
    {
        $this->applyFilterTimeRange($query, 'storage_fee_sku_statistics.closing_time', $input);
    }
}
