<?php

namespace Modules\Supplier\Services;

use Gobiz\ModelQuery\ModelQuery;
use Modules\Supplier\Commands\ListSupplier;
use Modules\Supplier\Models\Supplier;

class SupplierService implements SupplierServiceInterface
{
    /**
     * Khởi tạo đối tượng query suppliers
     *
     * @param array $filter
     * @return ModelQuery
     */
    public function query(array $filter)
    {
        return (new SupplierQuery())->query($filter);
    }

    /**
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Database\Eloquent\Collection|ListSupplier[]|Supplier|null
     */
    public function lists(array $filters)
    {
        return (new ListSupplier($filters))->handle();
    }
}
