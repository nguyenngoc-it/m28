<?php

namespace Modules\Supplier\Services;

use Gobiz\ModelQuery\ModelQuery;
use Gobiz\ModelQuery\ModelQueryFactory;
use Modules\Supplier\Models\Supplier;

class SupplierQuery extends ModelQueryFactory
{
    /**
     * Khởi tạo model
     */
    protected function newModel()
    {
        return new Supplier();
    }

    /**
     * @param ModelQuery $query
     * @param $code
     */
    protected function applyNameFilter(ModelQuery $query, $code)
    {
        $query->where('suppliers.name', 'LIKE', '%'.trim($code).'%');
    }

    /**
     * @param ModelQuery $query
     * @param $keyword
     */
    protected function applyKeywordFilter(ModelQuery $query, $keyword)
    {
        $keyword = trim($keyword);

        $query->where(function ($q) use ($keyword) {
            $q->where('suppliers.code', 'LIKE', '%'.$keyword.'%');
            $q->orWhere('suppliers.name', 'LIKE', '%'.$keyword.'%');
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
        $this->applyFilterTimeRange($query, 'suppliers.created_at', $input);
    }
}
