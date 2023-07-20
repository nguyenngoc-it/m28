<?php

namespace Modules\Category\Services;

use Gobiz\ModelQuery\ModelQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CategoryServiceInterface
{
    /**
     * Khởi tạo đối tượng query categories
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
