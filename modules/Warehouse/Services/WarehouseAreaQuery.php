<?php

namespace Modules\Warehouse\Services;

use Gobiz\ModelQuery\ModelQuery;
use Gobiz\ModelQuery\ModelQueryFactory;
use Modules\Warehouse\Models\WarehouseArea;

class WarehouseAreaQuery extends ModelQueryFactory
{
    /**
     * Khởi tạo model
     */
    protected function newModel()
    {
        return new WarehouseArea();
    }


    /**
     * Filter theo kho
     * @param ModelQuery $query
     * @param $warehouseId
     */
    protected function applyWarehouseIdsFilter(ModelQuery $query, $warehouseId)
    {
        if (is_array($warehouseId)) {
            $query->getQuery()->whereIn('warehouse_areas.warehouse_id', $warehouseId);
        } else {
            $query->where('warehouse_areas.warehouse_id', $warehouseId);
        }
    }

    /**
     * Filter theo 	thoi gian tao
     *
     * @param ModelQuery $query
     * @param array $input
     */
    protected function applyCreatedAtFilter(ModelQuery $query, array $input)
    {
        $this->applyFilterTimeRange($query, 'warehouse_areas.created_at', $input);
    }

    /**
     * Filter like theo name
     * @param ModelQuery $query
     * @param $name
     */
    protected function applyNameFilter(ModelQuery $query, $name)
    {
        $query->where('warehouse_areas.name', 'LIKE', '%'.trim($name).'%');
    }

    /**
     * Filter like theo code
     * @param ModelQuery $query
     * @param $code
     */
    protected function applyCodeFilter(ModelQuery $query, $code)
    {
        $query->where('warehouse_areas.code', 'LIKE', '%'.trim($code).'%');
    }
}
