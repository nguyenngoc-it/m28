<?php

namespace Modules\Category\Services;

use Gobiz\ModelQuery\ModelQuery;
use Modules\Category\Commands\ListCategory;
use Modules\Category\Models\Category;

class CategoryService implements CategoryServiceInterface
{
    /**
     * Khởi tạo đối tượng query categories
     *
     * @param array $filter
     * @return ModelQuery
     */
    public function query(array $filter)
    {
        return (new CategoryQuery())->query($filter);
    }

    /**
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Database\Eloquent\Collection|ListCategory[]|Category|null
     */
    public function lists(array $filters)
    {
        return (new ListCategory($filters))->handle();
    }
}
