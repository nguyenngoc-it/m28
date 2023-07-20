<?php

namespace Modules\Warehouse\Services;

use Gobiz\ModelQuery\ModelQuery;
use Gobiz\ModelQuery\ModelQueryFactory;
use Modules\Warehouse\Models\Warehouse;

class WarehouseQuery extends ModelQueryFactory
{
    /**
     * Khởi tạo model
     */
    protected function newModel()
    {
        return new Warehouse();
    }


    /**
     * Filter theo kho
     * @param ModelQuery $query
     * @param $warehouseId
     */
    protected function applyIdsFilter(ModelQuery $query, $warehouseId)
    {
        if (is_array($warehouseId)) {
            $query->getQuery()->whereIn('warehouses.id', $warehouseId);
        } else {
            $query->where('warehouses.id', $warehouseId);
        }
    }

    /**
     * @param ModelQuery $query
     * @param $code
     */
    protected function applyNameFilter(ModelQuery $query, $code)
    {
        $query->where('warehouses.name', 'LIKE', '%'.trim($code).'%');
    }

    /**
     * @param ModelQuery $query
     * @param $code
     */
    protected function applyCodeFilter(ModelQuery $query, $code)
    {
        $query->where('warehouses.code', 'LIKE', '%'.trim($code).'%');
    }

    /**
     * @param ModelQuery $query
     * @param $keyword
     */
    protected function applyKeywordFilter(ModelQuery $query, $keyword)
    {
        $keyword = trim($keyword);

        $query->where(function ($q) use ($keyword) {
            $q->where('warehouses.code', 'LIKE', '%'.$keyword.'%');
            $q->orWhere('warehouses.name', 'LIKE', '%'.$keyword.'%');
        });
    }

    /**
     * Filter theo 	thoi gian tao
     *
     * @param ModelQuery $query
     * @param array $input
     */
    protected function applyCreatedAtFilter(ModelQuery $query, array $input)
    {
        $this->applyFilterTimeRange($query, 'warehouses.created_at', $input);
    }
}
