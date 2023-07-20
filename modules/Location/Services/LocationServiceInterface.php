<?php

namespace Modules\Location\Services;

use Gobiz\ModelQuery\ModelQuery;
use Illuminate\Database\Eloquent\Collection;
use Modules\Location\Models\Location;

interface LocationServiceInterface
{
    /**
     * Khởi tạo đối tượng query locations
     *
     * @param array $filter
     * @return ModelQuery
     */
    public function query(array $filter);


    /**
     * Lấy thông tin danh sách giao dịch viên
     *
     * @param array $filters
     * @return Location|null
     */
    public function lists(array $filters);

    /**
     * Ds Countries đang sử dụng
     *
     * @return Collection
     */
    public function activeCountries();
}
