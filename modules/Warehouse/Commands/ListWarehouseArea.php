<?php

namespace Modules\Warehouse\Commands;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Modules\Service;

class ListWarehouseArea
{
    protected $filter;

    /**
     * @var mixed|string
     */
    protected $sort = 'desc';

    /**
     * @var mixed|string
     */
    protected $sortBy = 'id';

    /**
     * ListWarehouseArea constructor.
     * @param array $filter
     */
    public function __construct(array $filter)
    {
        $this->filter = $filter;
        $this->sort   = isset($this->filter['sort']) ? $this->filter['sort'] : 'desc';
        $this->sortBy = isset($this->filter['sortBy']) ? $this->filter['sortBy'] : 'id';
    }


    /**
     * @return LengthAwarePaginator
     */
    public function handle()
    {
        $filter = $this->filter;
        foreach (['sort', 'sortBy', 'page', 'per_page', 'export', 'paginate'] as $p) {
            if (isset($filter[$p])) {
                unset($filter[$p]);
            }
        }

        $page     = Arr::get($this->filter, 'page', config('paginate.page'));
        $per_page = Arr::get($this->filter, 'per_page', config('paginate.per_page'));

        $query = Service::warehouse()->queryWarehouseArea($filter)->getQuery();
        $query = $query->whereNull('status');
        $query = $this->setOrderBy($query);

        return $query->paginate($per_page, ['warehouse_areas.*'], 'page', $page);
    }


    /**
     * @param Builder $query
     * @return Builder
     */
    protected function setOrderBy($query)
    {
        $sortBy = $this->sortBy;

        $sort  = $this->sort;
        $table = 'warehouse_areas';
        $query->orderBy($table . '.' . $sortBy, $sort);

        return $query;
    }
}
