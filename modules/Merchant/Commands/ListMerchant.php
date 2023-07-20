<?php
namespace Modules\Merchant\Commands;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Modules\Service;
use Modules\Merchant\Models\Merchant;

class ListMerchant
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
     * ListMerchant constructor.
     * @param array $filter
     */
    public function __construct(array $filter)
    {
        $this->filter = $filter;
        $this->sort   = isset($this->filter['sort']) ? $this->filter['sort'] : 'desc';
        $this->sortBy = isset($this->filter['sortBy']) ? $this->filter['sortBy'] : 'id';
    }


    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|Builder[]|\Illuminate\Database\Eloquent\Collection
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
            return Service::merchant()->query($filter)->getQuery()->get();
        }

        $page = Arr::get($this->filter, 'page', config('paginate.page'));
        $per_page = Arr::get($this->filter, 'per_page', config('paginate.per_page'));

        $query = Service::merchant()->query($filter)->getQuery();
        $query = $this->setOrderBy($query);


        return $query->paginate($per_page, ['merchants.*'], 'page', $page);
    }


    /**
     * @param Builder $query
     * @return Builder
     */
    protected function setOrderBy($query)
    {
        $sortBy = $this->sortBy;

        $sort = $this->sort;
        $table = 'merchants';
        $query->orderBy($table . '.' . $sortBy, $sort);

        return $query;
    }
}
