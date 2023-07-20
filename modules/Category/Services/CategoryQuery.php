<?php

namespace Modules\Category\Services;

use Gobiz\ModelQuery\ModelQuery;
use Gobiz\ModelQuery\ModelQueryFactory;
use Modules\Category\Models\Category;

class CategoryQuery extends ModelQueryFactory
{
    /**
     * Khởi tạo model
     */
    protected function newModel()
    {
        return new Category();
    }

    /**
     * @param ModelQuery $query
     * @param $code
     */
    protected function applyNameFilter(ModelQuery $query, $code)
    {
        $query->where('categories.name', 'LIKE', '%'.trim($code).'%');
    }

    /**
     * @param ModelQuery $query
     * @param $keyword
     */
    protected function applyKeywordFilter(ModelQuery $query, $keyword)
    {
        $keyword = trim($keyword);

        $query->where(function ($q) use ($keyword) {
            $q->where('categories.code', 'LIKE', '%'.$keyword.'%');
            $q->orWhere('categories.name', 'LIKE', '%'.$keyword.'%');
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
        $this->applyFilterTimeRange($query, 'categories.created_at', $input);
    }
}
