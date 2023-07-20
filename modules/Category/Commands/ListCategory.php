<?php
namespace Modules\Category\Commands;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Modules\Service;

class ListCategory
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
     * ListCategory constructor.
     * @param array $filter
     */
    public function __construct(array $filter)
    {
        $this->filter = $filter;
        $this->sort   = isset($this->filter['sort']) ? $this->filter['sort'] : 'desc';
        $this->sortBy = isset($this->filter['sortBy']) ? $this->filter['sortBy'] : 'id';
    }


    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function handle()
    {
        $filter = $this->filter;
        foreach (['sort', 'sortBy', 'page', 'per_page', 'export', 'paginate'] as $p) {
            if (isset($filter[$p])) {
                unset($filter[$p]);
            }
        }

        $paginate = Arr::get($this->filter, 'paginate', true);
        if(!$paginate) {
            return Service::category()->query($filter)->getQuery()->get();
        }

        $page = Arr::get($this->filter, 'page', config('paginate.page'));
        $per_page = Arr::get($this->filter, 'per_page', config('paginate.per_page'));

        $query = Service::category()->query($filter)->getQuery();
        $query = $this->setOrderBy($query);


        return $query->paginate($per_page, ['categories.*'], 'page', $page);
    }


    /**
     * @param Builder $query
     * @return Builder
     */
    protected function setOrderBy($query)
    {
        $sortBy = $this->sortBy;

        $sort = $this->sort;
        $table = 'categories';
        $query->orderBy($table . '.' . $sortBy, $sort);

        return $query;
    }
}
