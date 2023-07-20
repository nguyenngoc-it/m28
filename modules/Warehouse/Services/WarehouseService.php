<?php

namespace Modules\Warehouse\Services;

use Gobiz\ModelQuery\ModelQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Warehouse\Commands\ListWarehouse;
use Modules\Warehouse\Commands\ListWarehouseArea;
use Modules\Warehouse\Models\Warehouse;

class WarehouseService implements WarehouseServiceInterface
{
    /**
     * Khởi tạo đối tượng query warehouses
     *
     * @param array $filter
     * @return ModelQuery
     */
    public function query(array $filter)
    {
        return (new WarehouseQuery())->query($filter);
    }


    /**
     * @param array $filters
     * @return LengthAwarePaginator|Collection
     */
    public function lists(array $filters)
    {
        return (new ListWarehouse($filters))->handle();
    }

    /**
     * Khởi tạo đối tượng query warehouse area
     *
     * @param array $filter
     * @return ModelQuery
     */
    public function queryWarehouseArea(array $filter)
    {
        return (new WarehouseAreaQuery())->query($filter);
    }

    /**
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function listWarehouseArea(array $filters)
    {
        return (new ListWarehouseArea($filters))->handle();
    }
}
