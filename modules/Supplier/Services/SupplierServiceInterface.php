<?php

namespace Modules\Supplier\Services;

use Gobiz\ModelQuery\ModelQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SupplierServiceInterface
{
    /**
     * Khởi tạo đối tượng query suppliers
     *
     * @param array $filter
     * @return ModelQuery
     */
    public function query(array $filter);

    /**
     * Lấy danh sách
     *
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function lists(array $filters);
}
