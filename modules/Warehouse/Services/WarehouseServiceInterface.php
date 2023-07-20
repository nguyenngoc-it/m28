<?php

namespace Modules\Warehouse\Services;

use Gobiz\ModelQuery\ModelQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface WarehouseServiceInterface
{
    /**
     * Khởi tạo đối tượng query warehouses
     *
     * @param array $filter
     * @return ModelQuery
     */
    public function query(array $filter);

    /**
     * Lấy thông tin danh sách giao dịch viên
     *
     * @param array $filters
     * @return LengthAwarePaginator|Collection
     */
    public function lists(array $filters);

    /**
     * Khởi tạo đối tượng query warehouse area
     *
     * @param array $filter
     * @return ModelQuery
     */
    public function queryWarehouseArea(array $filter);

    /**
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function listWarehouseArea(array $filters);
}
