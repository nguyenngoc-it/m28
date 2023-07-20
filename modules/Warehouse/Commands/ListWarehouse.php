<?php

namespace Modules\Warehouse\Commands;

use Gobiz\Support\Helper;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Modules\Service;

class ListWarehouse
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
     * ListWarehouse constructor.
     * @param array $filter
     */
    public function __construct(array $filter)
    {
        $this->filter = $filter;
        $this->sort   = isset($this->filter['sort']) ? $this->filter['sort'] : 'desc';
        $this->sortBy = isset($this->filter['sortBy']) ? $this->filter['sortBy'] : 'id';
    }


    /**
     * @return LengthAwarePaginator|Collection
     */
    public function handle()
    {
        $filter = $this->filter;
        foreach (['sort', 'sortBy', 'page', 'per_page', 'export', 'paginate', 'select'] as $p) {
            if (isset($filter[$p])) {
                unset($filter[$p]);
            }
        }

        $paginate = Arr::get($this->filter, 'paginate', true);
        if (!$paginate) {
            $query = Service::warehouse()->query($filter)->getQuery();
            if (!empty($this->filter['select'])) {
                $query->select($this->filter['select']);
            }
            return $query->get();
        }
        $page     = Arr::get($this->filter, 'page', config('paginate.page'));
        $per_page = Arr::get($this->filter, 'per_page', config('paginate.per_page'));

        $query = Service::warehouse()->query($filter)->getQuery();
        $query->with(['country', 'province', 'district', 'ward']);
        $query = $this->setOrderBy($query);

        return $query->paginate($per_page, !empty($this->filter['select']) ? $this->filter['select'] : ['warehouses.*'], 'page', $page);
    }


    /**
     * @param Builder $query
     * @return Builder
     */
    protected function setOrderBy($query)
    {
        $sortBy = $this->sortBy;

        $sort  = $this->sort;
        $table = 'warehouses';
        $query->orderBy($table . '.' . $sortBy, $sort);

        return $query;
    }
}
