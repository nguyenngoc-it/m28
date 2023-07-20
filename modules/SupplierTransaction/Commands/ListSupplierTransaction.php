<?php

namespace Modules\SupplierTransaction\Commands;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Modules\Service;

class ListSupplierTransaction
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

    public function __construct(array $filter)
    {
        $this->filter = $filter;
        $this->sort   = isset($this->filter['sort']) ? $this->filter['sort'] : 'desc';
        $this->sortBy = isset($this->filter['sortBy']) ? $this->filter['sortBy'] : 'id';
    }

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
            return Service::supplierTransaction()->query($filter)->getQuery()->get();
        }

        $page = Arr::get($this->filter, 'page', config('paginate.page'));
        $per_page = Arr::get($this->filter, 'per_page', config('paginate.per_page'));

        $query = Service::supplierTransaction()->query($filter)->getQuery();
        $query->with(['order', 'document', 'purchasingPackage', 'order.documents']);
        $query = $this->setOrderBy($query);


        return $query->paginate($per_page, ['supplier_transactions.*'], 'page', $page);
    }


    /**
     * @param Builder $query
     * @return Builder
     */
    protected function setOrderBy($query)
    {
        $sortBy = $this->sortBy;

        $sort = $this->sort;
        $table = 'supplier_transactions';
        $query->orderBy($table . '.' . $sortBy, $sort);

        return $query;
    }

}
